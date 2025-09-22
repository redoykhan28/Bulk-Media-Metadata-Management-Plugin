# Bulk Media Metadata Manager for WordPress


A powerful and user-friendly WordPress plugin designed to give you complete control over your Media Library's metadata. Manage image titles and alt text in bulk to dramatically improve your site's SEO and accessibility.

---

## Overview

The **Bulk Media Metadata Manager** is a comprehensive solution for anyone looking to efficiently manage their WordPress Media Library. Whether you're an SEO specialist, a content manager, or a developer, this plugin saves you hours of tedious manual editing by providing a suite of powerful bulk-processing tools.

From exporting your entire library for an audit to importing thousands of changes from a single CSV file, this plugin makes professional media management accessible to everyone.

---

## Features

This plugin is packed with features designed for flexibility, safety, and power.

### Export to CSV
*   **Complete Library Export:** Generate a CSV file of your entire media library with one click.
*   **Essential Fields Included:** The export contains `image_url`, `title`, and `alt_text` for every image.
*   **Backup & Audit:** Use the exported file as a secure backup or as a starting point for a comprehensive content audit.

### Import from CSV
*   **Flexible Updates:** Upload a CSV to update image titles, alt text, or both. The importer intelligently detects which columns you've provided and only updates that data.
*   **Smart URL Matching:** The importer automatically handles common URL rewrites (e.g., `/assets/` or `/storage/` instead of `/wp-content/uploads/`), ensuring the highest possible match rate for your images.
*   **Column Mapping:** The importer is designed to be flexible with your CSV format, requiring only an `image_url` column to function.

### Auto-Generate Alt Text
*   **Template-Based Generation:** Define a custom pattern to generate consistent, SEO-friendly alt text for thousands of images at once.
*   **Dynamic Placeholders:** Use placeholders like `[Image Title]`, `[Filename]`, and `[Site Name]` to create rich, descriptive alt text.
*   **Targeted Updates:** Choose to generate alt text for all images or only for images where it is currently missing.

### "Dry Run" Mode (Safety First!)
*   **Preview All Changes:** Before committing any changes to your database, run any import or generation in "Dry Run" mode.
*   **Detailed Reporting:** Get a full report of which images were found and what changes would be made, giving you complete confidence before a live run.

---

## How to Use

1.  **Export (Optional but Recommended):**
    *   Navigate to **Media > Media Metadata**.
    *   Click "Export Media Library Metadata" to download a CSV of your current data.

2.  **Import:**
    *   Open your CSV in any spreadsheet editor and make your desired changes.
    *   On the plugin page, click "Choose File" and select your CSV.
    *   Run a **Dry Run** first to preview the changes.
    *   Uncheck the "Dry Run Mode" box and run the import again to make the changes live.

3.  **Auto-Generate:**
    *   Define your alt text structure in the "Generation Template" field.
    *   Select your "Targeting Options."
    *   Run a **Dry Run** to see how many images will be affected.
    *   Uncheck the "Dry Run Mode" box and run the generation again to apply the new alt text.

---

## Installation

1.  Download the latest version of the plugin as a ZIP file from the [Releases] page.
2.  In your WordPress admin dashboard, go to **Plugins > Add New**.
3.  Click **Upload Plugin** and select the ZIP file you downloaded.
4.  Activate the plugin.
5.  You will find the plugin's admin page under **Media > Media Metadata**.

---

## To-Do / Future Enhancements

This plugin has a strong foundation, but there is always room to grow. Future enhancements may include:

*   [ ] Advanced export filters (e.g., by date range or missing metadata).
*   [ ] Integration with AI services (like OpenAI or Google Gemini) for intelligent, vision-based alt text generation.
*   [ ] Support for other metadata like captions and descriptions.
*   [ ] A dedicated "Settings" page for API keys and advanced options.

---

## Contributing

Contributions are welcome! If you have an idea for a new feature or have found a bug, please open an issue or submit a pull request.
