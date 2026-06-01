<?php
namespace CRWP;

defined( 'ABSPATH' ) || exit;

/**
 * Self-hosted update channel backed by GitHub Releases.
 *
 * No third-party libraries — pure wp_remote_*, matching the rest of the
 * plugin. Reads the repo's latest published Release, and when its tag is newer
 * than the installed version, surfaces it through WordPress's normal plugin
 * update flow (Dashboard → Updates, the Plugins screen, and "View details").
 *
 * The Release must carry the built `cardology-reports-<version>.zip` asset
 * produced by .github/workflows/release.yml — that zip nests everything under
 * a `cardology-reports/` folder so it installs cleanly over the existing copy.
 */
final class Updater {

	private const REPO       = 'webdeeva/cardology-reports-wp';
	private const CACHE_KEY  = 'crwp_update_release';
	private const CACHE_TTL  = 6 * HOUR_IN_SECONDS;
	private const MISS_TTL   = 30 * MINUTE_IN_SECONDS;

	private string $file;
	private string $basename;
	private string $slug;

	public function __construct( string $file ) {
		$this->file     = $file;
		$this->basename = plugin_basename( $file );          // e.g. cardology-reports/cardology-reports.php
		$this->slug     = dirname( $this->basename );        // e.g. cardology-reports
	}

	public function register_hooks(): void {
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'inject_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 20, 3 );
		add_filter( 'upgrader_source_selection', array( $this, 'fix_source_dir' ), 10, 4 );
		add_action( 'upgrader_process_complete', array( $this, 'flush_cache' ) );
	}

	/**
	 * Inject our release into the list of available plugin updates.
	 *
	 * @param mixed $transient The update_plugins site transient.
	 * @return mixed
	 */
	public function inject_update( $transient ) {
		if ( ! is_object( $transient ) ) {
			return $transient;
		}

		$release = $this->fetch_release();
		if ( null === $release ) {
			return $transient;
		}

		$item = (object) array(
			'id'          => 'https://github.com/' . self::REPO,
			'slug'        => $this->slug,
			'plugin'      => $this->basename,
			'new_version' => $release['version'],
			'url'         => $release['homepage'],
			'package'     => $release['download_url'],
			'icons'       => array(),
			'banners'     => array(),
			'tested'      => $release['tested'],
			'requires'    => $release['requires'],
			'requires_php' => $release['requires_php'],
		);

		if ( version_compare( $release['version'], CRWP_VERSION, '>' ) && ! empty( $release['download_url'] ) ) {
			$transient->response[ $this->basename ] = $item;
		} else {
			// Listed as up-to-date so "View details" still resolves.
			unset( $transient->response[ $this->basename ] );
			$transient->no_update[ $this->basename ] = $item;
		}

		return $transient;
	}

	/**
	 * Provide data for the "View details" modal.
	 *
	 * @param mixed  $result Default false.
	 * @param string $action The plugins_api action.
	 * @param object $args   Arguments including the requested slug.
	 * @return mixed
	 */
	public function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) || $args->slug !== $this->slug ) {
			return $result;
		}

		$release = $this->fetch_release();
		if ( null === $release ) {
			return $result;
		}

		return (object) array(
			'name'          => 'Cardology Reports',
			'slug'          => $this->slug,
			'version'       => $release['version'],
			'author'        => '<a href="https://aquariusmaximus.com">Aquarius Maximus</a>',
			'homepage'      => $release['homepage'],
			'download_link' => $release['download_url'],
			'trunk'         => $release['download_url'],
			'requires'      => $release['requires'],
			'requires_php'  => $release['requires_php'],
			'tested'        => $release['tested'],
			'last_updated'  => $release['published_at'],
			'sections'      => array(
				'description' => 'Sell personalized Cardology reports on your WordPress site — Stripe Checkout, automatic generation via the Report Writer API, and email delivery.',
				'changelog'   => $release['changelog'],
			),
		);
	}

	/**
	 * Normalise the extracted folder name so the update lands in the existing
	 * plugin directory regardless of what GitHub named the archive root.
	 *
	 * @param string       $source        Path to the extracted source.
	 * @param string       $remote_source Path to the downloaded archive dir.
	 * @param \WP_Upgrader $upgrader      The upgrader instance.
	 * @param array        $hook_extra    Extra args identifying the update.
	 * @return string|\WP_Error
	 */
	public function fix_source_dir( $source, $remote_source, $upgrader, $hook_extra = array() ) {
		if ( empty( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->basename ) {
			return $source;
		}

		$desired = trailingslashit( $remote_source ) . $this->slug . '/';
		if ( $source === $desired ) {
			return $source;
		}

		global $wp_filesystem;
		if ( $wp_filesystem && $wp_filesystem->move( $source, $desired ) ) {
			return $desired;
		}

		return $source;
	}

	public function flush_cache(): void {
		delete_transient( self::CACHE_KEY );
	}

	/**
	 * Fetch and normalise the latest GitHub Release, with caching.
	 *
	 * @return array<string,mixed>|null Null when no usable release is available.
	 */
	private function fetch_release(): ?array {
		$cached = get_transient( self::CACHE_KEY );
		if ( is_array( $cached ) ) {
			return empty( $cached ) ? null : $cached;
		}

		$response = wp_remote_get(
			'https://api.github.com/repos/' . self::REPO . '/releases/latest',
			array(
				'timeout' => 10,
				'headers' => array(
					'Accept'     => 'application/vnd.github+json',
					'User-Agent' => 'cardology-reports-updater',
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			set_transient( self::CACHE_KEY, array(), self::MISS_TTL );
			return null;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) || empty( $data['tag_name'] ) ) {
			set_transient( self::CACHE_KEY, array(), self::MISS_TTL );
			return null;
		}

		// Prefer an attached .zip asset; fall back to the source zipball.
		$download_url = '';
		if ( ! empty( $data['assets'] ) && is_array( $data['assets'] ) ) {
			foreach ( $data['assets'] as $asset ) {
				if ( ! empty( $asset['browser_download_url'] ) && substr( $asset['name'] ?? '', -4 ) === '.zip' ) {
					$download_url = $asset['browser_download_url'];
					break;
				}
			}
		}
		if ( '' === $download_url && ! empty( $data['zipball_url'] ) ) {
			$download_url = $data['zipball_url'];
		}

		$release = array(
			'version'      => ltrim( (string) $data['tag_name'], 'vV' ),
			'download_url' => $download_url,
			'homepage'     => $data['html_url'] ?? ( 'https://github.com/' . self::REPO ),
			'changelog'    => $this->format_changelog( $data['body'] ?? '' ),
			'published_at' => ! empty( $data['published_at'] ) ? gmdate( 'Y-m-d', strtotime( $data['published_at'] ) ) : '',
			'requires'     => '6.4',
			'requires_php' => '8.0',
			'tested'       => get_bloginfo( 'version' ),
		);

		set_transient( self::CACHE_KEY, $release, self::CACHE_TTL );
		return $release;
	}

	/**
	 * Render the release notes (Markdown-ish) as simple HTML for the modal.
	 */
	private function format_changelog( string $body ): string {
		$body = trim( $body );
		if ( '' === $body ) {
			return '<p>See the GitHub release for details.</p>';
		}
		$escaped = esc_html( $body );
		return '<pre style="white-space:pre-wrap;font-family:inherit;">' . $escaped . '</pre>';
	}
}
