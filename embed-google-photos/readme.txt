=== Gallery for Google Photos – Import and Display Photo Albums ===
Contributors: abuhayat, bplugins, btechnologies
Donate link: https://www.buymeacoffee.com/abuhayat
Tags: block, photos, google photos, gallery, album
Requires at least: 6.5
Tested up to: 7.0
Stable tag: 1.2.1
Requires PHP: 7.1
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Embed stunning Google Photos galleries directly into your WordPress site with the Embed Google Photos plugin.

== Description ==

The Embed Google Photos plugin offers a seamless solution for integrating beautiful Google Photos galleries directly into your WordPress website. With its intuitive interface and robust functionality, this plugin simplifies the process of showcasing your photos and galleries in a visually stunning way. Whether you're a photographer, blogger, or business owner, this plugin provides a convenient and efficient way to share your visual content with your audience. With features like customizable layouts, responsive design, and easy-to-use settings, you can effortlessly create and manage your Google Photos galleries with ease. Say goodbye to complex embedding codes and manual updates – streamline your workflow and enhance your website's visual appeal with the Embed Google Photos plugin.

**[Demos](https://bplugins.com/products/embed-google-photos/#demos)** | **[Documentation](https://bplugins.com/docs/embed-google-photos/)**  

= Features =
- Display all photos from Google Photos.
- Show all albums contained within Google Photos.
- Display photos from specific albums.
- Customize the layout columns with various options.
- Set image aspect ratios.
- Customize the "Load More" button's color, typography, border, and padding.
- Add borders to images.

= How to use =
- First, install the Embed Google Photos plugin.
- Add the Embed Google Photos from the block category called "Widgets" in the Gutenberg editor.
- You can change block settings from the right-side settings sidebar.
- Enjoy!

* For installation help click on Installation Tab
 

= Feedback =
- Liked that plugin? Hate it? Want a new feature? [Send me some feedback](mailto:support@bplugins.com 'Send feedback')


== Installation ==

= From Gutenberg Editor: =
1. Go to the WordPress Block/Gutenberg Editor
2. Search For **Embed Google Photos**
3. Click on the **Embed Google Photos** to add the block

= Download & Upload: =
1. Download the **Embed Google Photos** plugin (*.zip file*)
2. In your admin area, go to the Plugins menu and click on **Add New**
3. Click on **Upload Plugin** and choose the **`embed-google-photos.zip`** file and click on **Install Now**
4. Activate the plugin and Enjoy!

= Manually: =
1. Download and upload the **Embed Google Photos** plugin to the **`/wp-content/plugins/`** directory
2. Activate the plugin through the Plugins menu in WordPress


== Frequently Asked Questions ==

= Is Embed Google Photos block free? =

Yes, Embed Google Photos block is a free Gutenberg block plugin.

= Can I show all Google Photos? =

Yes, you can display all Google Photos.

= Can I show just the album list? =

Yes, you can display the entire album list.

= Can I show photos from a specific album only? =

Yes, you can display all images from a specific album.

= Can I filter images and videos? =

Yes, you can filter both images and videos.

= Can I show my favorite images? =

Yes, you can display your favorite images.

= Does it work with any WordPress theme? =

Yes, it will work with any standard WordPress theme.

= Can I change block settings? =

Yes, you can change block settings from the Gutenberg block editor's right sidebar.

= How many times can I reuse a block? =

You can use unlimited times as you want.

= Where can I get support? =

You can post your questions on the [support forum here](https://wordpress.org/support/plugin/embed-google-photos/)

== Screenshots ==

1. Settings
2. Default
3. Favorite
4. Only Photos
5. Only Videos
6. Specific Album
7. Ratio Square
8. Ratio Landscape
9. Ratio Horizontal
10. Ratio Potrait


== Changelog ==

= 1.2.1 - 14 July, 2026 =
* Important: Google retired the old Photos Library API, so older galleries may stop showing images. Now uses Google's new Photos Picker — reconnect your account and re-select your photos (they import into your Media Library and keep working).
* New: video support, Grid/Masonry/Carousel layouts, Load More & Pagination, captions, and image aspect-ratio & border controls.
* Performance/SEO: lazy-load with blur placeholder, responsive images (srcset), Schema.org image markup, and per-image alt text.
* Security: fixed a stored XSS in the lightbox; made Google credentials (Client ID/Secret/Refresh Token) write-only and server-side only; closed the earlier OAuth token exposure.
* Improved: redesigned authorization flow; resolved all Plugin Check findings; documented external services and bundled third-party libraries.

= 1.0.9 - 28 Dec, 2024 =
* Frontend useAjax use.

= 1.0.8 - 12 Dec, 2024 =
* Extra code remove.

= 1.0.7 - 8 Dec, 2024 =
* Some issues problem.

= 1.0.6 - 27 Nov, 2024 =
* No publicly documented resource for your compressed content
* Undocumented use of a 3rd Party / external service

= 1.0.5 - 21 Nov,24 =
* Trademark issues solved

= 1.0.4 - 9 Oct, 2024 =
* Solved the problem where album list images were not found.

= 1.0.3 - 12 sept, 2024 =
* Fixed the popup image resolution.

= 1.0.2 - 16 July, 2024 =
* Load more button update.

= 1.0.1 - 18 May,2024 =
* Authorization issues fixed.

= 1.0.0 =
* Initial Release


== Upgrade Notice ==

= 1.2.1 =
Important: Google shut down the old Google Photos API this plugin used, so galleries built the old way may no longer show your images. This version adds Google's new Photos Picker system — after updating, open the block, reconnect your Google account, and re-select your photos (they are now saved to your own Media Library and keep working). Also a recommended security fix (stored XSS + write-only credentials).

= 1.0.9 - 28 Dec, 2024 =
* Frontend useAjax use.

= 1.0.8 - 12 Dec, 2024 =
* Extra code remove.

= 1.0.7 - 8 Dec, 2024 =
* Some issues problem.

= 1.0.6 - 27 Nov, 2024 =
* No publicly documented resource for your compressed content
* Undocumented use of a 3rd Party / external service

= 1.0.5 - 21 Nov,24 =
* Trademark issues solved

= 1.0.4 - 9 Oct, 2024 =
* Solved the problem where album list images were not found.

= 1.0.3 - 12 sept, 2024 =
* Fixed the popup image resolution.

= 1.0.2 - 16 July, 2024 =
* Load more button update.

= 1.0.1 - 18 May,2024 =
* Authorization issues fixed.

= 1.0.0 =
* Initial Release


== Source Code ==

You can find the source code, report bugs, and contribute to the development of this plugin on our GitHub repository:
[**Embed Google Photos on GitHub**](https://github.com/bPlugins/embed-google-photos) 


== External Services ==

This plugin connects to the following external services. Understanding what is sent, and when, helps clarify how your data is handled.

= Google Photos (Google OAuth 2.0 + Google Photos Picker API) =

What it is and why it is used: This plugin is a Google Photos gallery. To let you pick photos and videos from your own Google Photos account and display them on your site, it connects to Google's APIs on your server. This connection only happens after you enter your own Google API credentials (Client ID, Client Secret, Refresh Token) in the plugin settings and use the "Select Photos" flow; it is required for the plugin to function.

Which endpoints are contacted and what data is sent:
* https://oauth2.googleapis.com/token — your Client ID, Client Secret and Refresh Token are sent to exchange them for a short-lived access token.
* https://photospicker.googleapis.com/v1 — the access token is sent (as a Bearer token) to create a picker session, check its status, and list the media items you selected. The selected photos/videos are then downloaded to your own WordPress Media Library.

When it happens: only in wp-admin, triggered by an authenticated administrator connecting an account or clicking "Select Photos". No data is sent from your visitors' browsers, and the OAuth credentials/tokens never reach the frontend.

Your credentials and tokens are stored only on your own server (in your WordPress database) and are sent only to Google. This plugin does not transmit them to bPlugins or any other third party.

Google's terms and privacy policy:
* Terms of Service: https://policies.google.com/terms
* Privacy Policy: https://policies.google.com/privacy
* Google APIs Terms of Service: https://developers.google.com/terms

= Schema.org vocabulary (https://schema.org) =

What it is and why it is used: On the frontend the plugin outputs Schema.org "ImageObject" structured data (JSON-LD) for each gallery image so search engines such as Google can better understand and index your images (Google Images / rich results, improving SEO).

What data is sent: None. The address "https://schema.org" appears only as the standard `@context` identifier inside the JSON-LD markup — it is a vocabulary name, not a network request. The plugin does not connect to, load anything from, or send any data to schema.org. All image data stays on your own server.

Terms & privacy: https://schema.org/docs/terms.html

== Third-Party Libraries ==

This plugin bundles the following third-party JavaScript/PHP libraries.

= Swiper =
* **Source:** https://swiperjs.com/
* **GitHub:** https://github.com/nolimits4web/swiper
* **License:** MIT – https://github.com/nolimits4web/swiper/blob/master/LICENSE
* **Purpose:** Provides the "Carousel" gallery layout — a touch-enabled slider with autoplay, loop, navigation arrows and pagination dots.

= Plyr =
* **Source:** https://plyr.io/
* **GitHub:** https://github.com/sampotts/plyr
* **License:** MIT – https://github.com/sampotts/plyr/blob/master/LICENSE.md
* **Purpose:** A simple, accessible HTML5 media player used to play gallery videos inside the lightbox.

= Fancybox (@fancyapps/ui) =
* **Source:** https://fancyapps.com/fancybox/
* **GitHub:** https://github.com/fancyapps/ui
* **License:** Fancyapps UI License – https://fancyapps.com/pricing/ (note: this is a proprietary license, not MIT/GPL — see the security/compatibility note below).
* **Purpose:** The lightbox that opens photos and videos in a full-screen viewer.

= Immer =
* **Source:** https://immerjs.github.io/immer/
* **GitHub:** https://github.com/immerjs/immer
* **License:** MIT – https://github.com/immerjs/immer/blob/main/LICENSE
* **Purpose:** Enables safe, immutable updates of block settings inside the Gutenberg editor.

= Axios =
* **Source:** https://axios-http.com/
* **GitHub:** https://github.com/axios/axios
* **License:** MIT – https://github.com/axios/axios/blob/v1.x/LICENSE
* **Purpose:** Promise-based HTTP client used for the plugin's admin/editor AJAX requests.

= React Router (react-router-dom) =
* **Source:** https://reactrouter.com/
* **GitHub:** https://github.com/remix-run/react-router
* **License:** MIT – https://github.com/remix-run/react-router/blob/main/LICENSE.md
* **Purpose:** Client-side routing for the plugin's admin "Demo & Help" dashboard.

= Font Awesome =
* **Source:** https://fontawesome.com/
* **GitHub:** https://github.com/FortAwesome/Font-Awesome
* **License:** SIL OFL 1.1 (Fonts), MIT (CSS), CC BY 4.0 (Icons) – https://fontawesome.com/license/free
* **Purpose:** Provides scalable vector icons used in the block and editor interface.

= bpl-tools =
* **Source / GitHub:** https://github.com/bPlugins/bpl-tools
* **License:** GPL-2.0-or-later – https://www.gnu.org/licenses/gpl-2.0.html
* **Purpose:** Shared utility library providing admin dashboard components and common Gutenberg editor controls.
* **External Services:** The library may connect to bPlugins, WordPress.org, and Freemius services for product data and checkout functionality. See full details: https://github.com/bPlugins/bpl-tools#external-requests--why-they-are-made

= Freemius Lite SDK =
* **Source:** https://bplugins.com/
* **GitHub:** https://github.com/bPlugins/freemius-lite-sdk
* **License:** GPL-2.0-or-later – https://www.gnu.org/licenses/gpl-2.0.html
* **Purpose:** Provides an opt-in consent form for usage tracking and analytics to help improve the plugin. No data is sent before explicit user consent.
* **External Services:** Communicates with `api.bplugins.com` (activation events) and `wp.freemius.com` (opt-in processing) only after user opt-in. See [bPlugins Privacy Policy](https://bplugins.com/privacy-policy) and [Freemius Privacy Policy](https://freemius.com/privacy/).