<?php
/**
 * BFCM 2026 Banner & Admin Notice for Gallery for Google Photos
 *
 * Handles the Black Friday / Cyber Monday 2026 promotion banner and notice.
 * Expiry: December 5, 2026.
 *
 * @package EmbedGooglePhotos
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'BPGPB_BFCM_Notice' ) ) {
    class BPGPB_BFCM_Notice {

        private static $instance = null;

        /**
         * End date for BFCM 2026 promotion (December 5, 2026 23:59:59 UTC).
         */
        const EXPIRY_TIMESTAMP = 1796515199;

        public static function get_instance() {
            if ( null === self::$instance ) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function __construct() {
            add_action( 'all_admin_notices', [ $this, 'render_all_google_photos_banner' ] );
            add_action( 'wp_ajax_bpgpb_dismiss_bfcm_notice', [ $this, 'ajax_dismiss' ] );
        }

        /**
         * Check if promotion is currently active and not dismissed.
         */
        public function is_active() {
            if ( time() > self::EXPIRY_TIMESTAMP ) {
                return false;
            }

            if ( get_user_meta( get_current_user_id(), 'bpgpb_bfcm_2026_dismissed', true ) ) {
                return false;
            }

            if ( function_exists( 'bpgpb_fs' ) && bpgpb_fs()->can_use_premium_code() ) {
                return false;
            }

            return true;
        }

        /**
         * AJAX handler to persist dismissal for the user.
         */
        public function ajax_dismiss() {
            check_ajax_referer( 'bpgpb_bfcm_dismiss_nonce', 'nonce' );

            if ( ! current_user_can( 'read' ) ) {
                wp_send_json_error( [ 'message' => 'Unauthorized' ] );
            }

            update_user_meta( get_current_user_id(), 'bpgpb_bfcm_2026_dismissed', 1 );
            wp_send_json_success();
        }

        /**
         * Render the BFCM banner on the All Google Photos list page (edit.php?post_type=bpgpb_gallery).
         */
        public function render_all_google_photos_banner() {
            global $pagenow;

            if ( 'edit.php' !== $pagenow || ! isset( $_GET['post_type'] ) || 'bpgpb_gallery' !== $_GET['post_type'] || isset( $_GET['page'] ) ) {
                return;
            }

            $screen = get_current_screen();
            if ( $screen && 'edit-bpgpb_gallery' !== $screen->id ) {
                return;
            }

            if ( ! $this->is_active() ) {
                return;
            }

            $nonce       = wp_create_nonce( 'bpgpb_bfcm_dismiss_nonce' );
            $pricing_url = 'https://bplugins.com/products/embed-google-photos/pricing/?utm_source=all_google_photos&utm_medium=bfcm_banner&utm_campaign=bfcm2026';
            ?>
            <div id="bpgpbBfcmBannerWrap" style="display:none;">
                <style>
                    /* ===== BFCM Banner: Full-width with 20px right gap ===== */
                    #bpgpbBfcmBannerWrap {
                        display: none;
                        width: 100%;
                        box-sizing: border-box;
                        padding-right: 20px;
                        margin: 0;
                        clear: both;
                    }
                    #bpgpbBfcmBannerWrap .bpgpbBfcmBanner {
                        display: flex;
                        align-items: stretch;
                        background: #ffffff;
                        border: 1px solid #d1e1fb;
                        border-left: none;
                        border-radius: 0;
                        box-shadow: 0 2px 12px rgba(20, 110, 245, 0.08);
                        margin: 0;
                        width: 100%;
                        overflow: hidden;
                        position: relative;
                        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                        box-sizing: border-box;
                    }
                    #bpgpbBfcmBannerWrap .bpgpbBfcmBanner * {
                        box-sizing: border-box;
                    }
                    #bpgpbBfcmBannerWrap .bpgpbBfcmRibbon {
                        background: linear-gradient(135deg, #EEF4FF 0%, #E3EDFE 50%, #D8E6FD 100%);
                        border-right: 1px solid #d1e1fb;
                        padding: 12px 18px;
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        justify-content: center;
                        min-width: 140px;
                        flex-shrink: 0;
                    }
                    #bpgpbBfcmBannerWrap .bpgpbBfcmRibbonIconWrap {
                        width: 44px;
                        height: 44px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    }
                    #bpgpbBfcmBannerWrap .bpgpbBfcmRibbonIcon,
                    #bpgpbBfcmBannerWrap .bpgpbBfcmRibbonIconWrap svg {
                        width: 100%;
                        height: 100%;
                        display: block;
                        object-fit: contain;
                        filter: drop-shadow(0 4px 10px rgba(20, 110, 245, 0.25));
                    }
                    #bpgpbBfcmBannerWrap .bpgpbBfcmRibbonTitle {
                        color: #001737;
                        font-size: 10px;
                        font-weight: 800;
                        letter-spacing: 0.8px;
                        margin-top: 6px;
                        text-transform: uppercase;
                        text-align: center;
                        line-height: 1.2;
                    }
                    #bpgpbBfcmBannerWrap .bpgpbBfcmContent {
                        flex: 1;
                        padding: 12px 20px;
                        display: flex;
                        flex-direction: column;
                        justify-content: center;
                        gap: 6px;
                    }
                    #bpgpbBfcmBannerWrap .bpgpbBfcmHeaderRow {
                        display: flex;
                        align-items: center;
                        gap: 10px;
                        flex-wrap: wrap;
                    }
                    #bpgpbBfcmBannerWrap .bpgpbBfcmTitle {
                        margin: 0;
                        font-size: 15px;
                        font-weight: 700;
                        color: #070127;
                        line-height: 1.3;
                    }
                    #bpgpbBfcmBannerWrap .bpgpbBfcmDesc {
                        margin: 3px 0 8px 0;
                        font-size: 12px;
                        line-height: 1.5;
                        color: #4b5563;
                        max-width: 950px;
                    }
                    #bpgpbBfcmBannerWrap .bpgpbBfcmFeaturesRow {
                        display: flex;
                        align-items: center;
                        flex-wrap: wrap;
                        gap: 16px;
                    }
                    #bpgpbBfcmBannerWrap .bpgpbBfcmFeatureItem {
                        display: inline-flex;
                        align-items: center;
                        gap: 5px;
                        font-size: 12px;
                        color: #4b5563;
                        font-weight: 500;
                    }
                    #bpgpbBfcmBannerWrap .bpgpbBfcmCheckmark {
                        color: #070127;
                        font-weight: 700;
                        font-size: 12px;
                    }
                    #bpgpbBfcmBannerWrap .bpgpbBfcmAction {
                        display: flex;
                        align-items: center;
                        padding-right: 22px;
                        padding-left: 10px;
                        position: relative;
                        flex-shrink: 0;
                    }
                    #bpgpbBfcmBannerWrap .bpgpbBfcmCloseBtn {
                        position: absolute;
                        top: 8px;
                        right: 10px;
                        background: transparent;
                        border: none;
                        cursor: pointer;
                        color: #0b81ee;
                        font-size: 18px;
                        line-height: 1;
                        padding: 2px 6px;
                        border-radius: 4px;
                        transition: color 0.15s ease, background 0.15s ease;
                    }
                    #bpgpbBfcmBannerWrap .bpgpbBfcmCloseBtn:hover {
                        color: #0860b3;
                        background: rgba(11, 129, 238, 0.1);
                    }
                    #bpgpbBfcmBannerWrap .bpgpbBfcmCtaBtn {
                        margin-right: 20px;
                        background: #e3edfe;
                        color: #0b81ee !important;
                        text-decoration: none !important;
                        font-size: 13px;
                        font-weight: 600;
                        padding: 9px 18px;
                        border-radius: 6px;
                        display: inline-flex;
                        align-items: center;
                        gap: 6px;
                        transition: all 0.2s ease;
                        box-shadow: none !important;
                        border: 1px solid rgba(11, 129, 238, 0.25);
                        white-space: nowrap;
                    }
                    #bpgpbBfcmBannerWrap .bpgpbBfcmCtaBtn:hover {
                        background: #d2e3fc;
                        color: #0860b3 !important;
                        transform: translateY(-1px);
                    }
                    @media (max-width: 900px) {
                        #bpgpbBfcmBannerWrap { padding-right: 12px; }
                        #bpgpbBfcmBannerWrap .bpgpbBfcmBanner { flex-direction: column; border-left: 1px solid #d1e1fb; }
                        #bpgpbBfcmBannerWrap .bpgpbBfcmRibbon { width: 100%; flex-direction: row; gap: 12px; padding: 8px 16px; border-right: none; border-bottom: 1px solid #d1e1fb; }
                        #bpgpbBfcmBannerWrap .bpgpbBfcmContent { padding: 12px 16px; }
                        #bpgpbBfcmBannerWrap .bpgpbBfcmAction { padding: 0 16px 12px; justify-content: flex-start; }
                        #bpgpbBfcmBannerWrap .bpgpbBfcmCloseBtn { top: 8px; right: 8px; }
                    }
                </style>
                <div class="bpgpbBfcmBanner">
                    <div class="bpgpbBfcmRibbon">
                        <div class="bpgpbBfcmRibbonIconWrap">
                            <?php echo $this->get_logo_svg(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </div>
                        <span class="bpgpbBfcmRibbonTitle">GOOGLE PHOTOS</span>
                    </div>

                    <div class="bpgpbBfcmContent">
                        <div class="bpgpbBfcmHeaderRow">
                            <h3 class="bpgpbBfcmTitle"><?php esc_html_e( "Gallery for Google Photos' Biggest Black Friday Sale is Live – Up to 45% OFF!", 'embed-google-photos' ); ?></h3>
                        </div>

                        <p class="bpgpbBfcmDesc"><?php esc_html_e( 'The biggest deal of the year just arrived early! This is your once-a-year opportunity to upgrade to Google Photos Gallery Pro at an exclusive price. Unlock unlimited shared albums, masonry & slider layouts, automated syncing, and everything you need to showcase your Google Photos galleries!', 'embed-google-photos' ); ?></p>

                        <div class="bpgpbBfcmFeaturesRow">
                            <span class="bpgpbBfcmFeatureItem"><span class="bpgpbBfcmCheckmark">✓</span> <?php esc_html_e( 'Unlimited Shared Albums', 'embed-google-photos' ); ?></span>
                            <span class="bpgpbBfcmFeatureItem"><span class="bpgpbBfcmCheckmark">✓</span> <?php esc_html_e( 'Masonry & Slider Layouts', 'embed-google-photos' ); ?></span>
                            <span class="bpgpbBfcmFeatureItem"><span class="bpgpbBfcmCheckmark">✓</span> <?php esc_html_e( 'Auto-Sync New Photos', 'embed-google-photos' ); ?></span>
                            <span class="bpgpbBfcmFeatureItem"><span class="bpgpbBfcmCheckmark">✓</span> <?php esc_html_e( 'Full Lightbox & Download', 'embed-google-photos' ); ?></span>
                        </div>
                    </div>

                    <div class="bpgpbBfcmAction">
                        <button type="button" class="bpgpbBfcmCloseBtn" id="bpgpbBfcmCloseBtn" aria-label="Dismiss banner" title="Dismiss">&times;</button>
                        <a href="<?php echo esc_url( $pricing_url ); ?>" target="_blank" rel="noopener noreferrer" class="bpgpbBfcmCtaBtn">
                            <?php esc_html_e( 'Unlock PRO Features →', 'embed-google-photos' ); ?>
                        </a>
                    </div>
                </div>
            </div>
            <script>
            (function($) {
                $(document).ready(function() {
                    if (localStorage.getItem('bpgpb_bfcm_2026_dismissed')) {
                        return;
                    }

                    $('#bpgpbBfcmBannerWrap').show();

                    $('#bpgpbBfcmCloseBtn').on('click', function(e) {
                        e.preventDefault();
                        $('#bpgpbBfcmBannerWrap').slideUp(200, function() {
                            $(this).remove();
                        });

                        localStorage.setItem('bpgpb_bfcm_2026_dismissed', '1');

                        $.post('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
                            action: 'bpgpb_dismiss_bfcm_notice',
                            nonce: '<?php echo esc_js( $nonce ); ?>'
                        });
                    });
                });
            })(jQuery);
            </script>
            <?php
        }

        /**
         * Return inlined official SVG logo of Gallery for Google Photos.
         */
        private function get_logo_svg() {
            return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" width="44" height="44" role="img" aria-label="Gallery for Google Photos">
  <defs>
    <clipPath id="egp-p-tile"><rect x="0" y="0" width="256" height="256" rx="56"/></clipPath>
    <clipPath id="egp-p-card"><rect x="40" y="48" width="176" height="160" rx="18"/></clipPath>
    <clipPath id="egp-p-thumb"><rect x="0" y="0" width="68" height="60" rx="9"/></clipPath>
    <linearGradient id="egp-p-bg" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#1e73ff"/><stop offset="1" stop-color="#6155f5"/></linearGradient>
    <radialGradient id="egp-p-glow" cx="0.5" cy="0.45" r="0.62"><stop offset="0" stop-color="#ffffff" stop-opacity="0.35"/><stop offset="1" stop-color="#ffffff" stop-opacity="0"/></radialGradient>
    <linearGradient id="egp-p-warm" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#ff9d5c"/><stop offset="1" stop-color="#ffd7a6"/></linearGradient>
    <linearGradient id="egp-p-cool" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#5aa8ff"/><stop offset="1" stop-color="#bfe2ff"/></linearGradient>
  </defs>
  <g clip-path="url(#egp-p-tile)">
    <rect x="0" y="0" width="256" height="256" fill="url(#egp-p-bg)"/>
    <circle cx="66" cy="60" r="120" fill="url(#egp-p-glow)"/>
    <rect x="40" y="48" width="176" height="160" rx="18" fill="#ffffff"/>
    <g clip-path="url(#egp-p-card)">
      <g transform="translate(54,62)"><g clip-path="url(#egp-p-thumb)"><rect width="68" height="60" fill="url(#egp-p-cool)"/><circle cx="50" cy="16" r="7" fill="#ffffff" opacity="0.9"/><polygon points="0,60 18,36 32,50 50,30 68,60" fill="#2f6bb0"/></g></g>
      <g transform="translate(134,62)"><g clip-path="url(#egp-p-thumb)"><rect width="68" height="60" fill="url(#egp-p-warm)"/><circle cx="20" cy="15" r="8" fill="#ffe08a"/><polygon points="0,60 20,38 36,52 52,32 68,60" fill="#b9552f"/></g></g>
      <g transform="translate(54,134)"><g clip-path="url(#egp-p-thumb)"><rect width="68" height="60" fill="url(#egp-p-warm)"/><circle cx="18" cy="16" r="8" fill="#ffd27a"/><polygon points="0,60 22,40 40,54 56,34 68,60" fill="#a94f2c"/></g></g>
      <g transform="translate(134,134)"><g clip-path="url(#egp-p-thumb)"><rect width="68" height="60" fill="url(#egp-p-cool)"/><circle cx="50" cy="16" r="7" fill="#ffffff" opacity="0.9"/><polygon points="0,60 16,38 34,52 50,30 68,60" fill="#2f6bb0"/></g></g>
    </g>
  </g>
</svg>';
        }
    }

    BPGPB_BFCM_Notice::get_instance();
}

