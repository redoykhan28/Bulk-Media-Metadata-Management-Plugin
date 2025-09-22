<?php
/**
 * Plugin Name:       Bulk Media Metadata Manager
 * Description:       A plugin to bulk export, import, and manage media metadata like titles and alt text via CSV.
 * Version:           1.0.0
 * Author:            Sezan Ahmed
 */

// Security check: Prevent direct access to the file.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Adds the plugin's menu item to the WordPress admin menu.
 */
function bmmm_add_admin_menu() {
    add_menu_page(
        'Bulk Media Metadata',          // The title that appears on the browser tab when you're on the page.
        'Media Metadata',               // The text that appears in the admin menu.
        'manage_options',               // The capability a user must have to see this menu item (administrator).
        'bulk-media-metadata-manager',  // The unique "slug" for this menu page.
        'bmmm_render_admin_page',       // The name of the function that will actually display the page's content.
        'dashicons-images-alt2',        // The icon for the menu item.
        25                              // The position in the menu.
    );
}
// This "hooks" our function into the WordPress 'admin_menu' action.
add_action( 'admin_menu', 'bmmm_add_admin_menu' );



/**
 * Renders the main admin page for the plugin.
 */
function bmmm_render_admin_page() {
    // Check permissions
    if ( ! current_user_can( 'manage_options' ) ) { return; }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <p>Welcome to the Bulk Media Metadata Manager. Use the tools below to manage your media library.</p>

        <!-- Section 1: Export to CSV (existing code) -->
        <div class="card" style="margin-bottom: 20px;">
            <h2>Export Media Library to CSV</h2>
            <form method="post">
                <?php wp_nonce_field( 'bmmm_export_action', 'bmmm_export_nonce' ); ?>
                <input type="submit" name="bmmm_export_csv" class="button button-primary" value="Export Media Library Metadata">
            </form>
        </div>

        <!-- Section 2: Import from CSV (existing code) -->
        <div class="card" style="margin-bottom: 20px;">
            <h2>Import from CSV</h2>
            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field( 'bmmm_import_action', 'bmmm_import_nonce' ); ?>
                <p><label for="bmmm_csv_file">Choose a CSV file to upload:</label><br><input type="file" id="bmmm_csv_file" name="bmmm_csv_file" accept=".csv" required></p>
                <p><label><input type="checkbox" name="bmmm_dry_run" value="1" checked> <strong>Dry Run Mode:</strong> Preview without saving changes.</label></p>
                <input type="submit" name="bmmm_import_csv" class="button button-primary" value="Upload and Process CSV">
            </form>
        </div>
        
        <!-- NEW Section 3: Auto-Generate Alt Text -->
        <div class="card">
            <h2>Auto-Generate Alt Text</h2>
            <p>Automatically generate alt text for your images based on a template. This is a powerful tool for improving SEO and accessibility.</p>
            <form method="post">
                <?php wp_nonce_field( 'bmmm_generate_action', 'bmmm_generate_nonce' ); ?>
                
                <p>
                    <label for="bmmm_alt_template"><strong>Generation Template:</strong></label><br>
                    <input type="text" id="bmmm_alt_template" name="bmmm_alt_template" class="large-text" value="[Image Title] - [Site Name]" required>
                    <small>Available placeholders: <code>[Image Title]</code>, <code>[Filename]</code>, <code>[Site Name]</code></small>
                </p>

                <p><strong>Targeting Options:</strong></p>
                <fieldset>
                    <label><input type="radio" name="bmmm_target_option" value="missing" checked> Only generate for images with missing alt text.</label><br>
                    <label><input type="radio" name="bmmm_target_option" value="all"> Overwrite all existing alt text with the generated text.</label>
                </fieldset>

                <p><label><input type="checkbox" name="bmmm_dry_run" value="1" checked> <strong>Dry Run Mode:</strong> Preview without saving changes.</label></p>

                <input type="submit" name="bmmm_generate_alt_text" class="button button-primary" value="Generate Alt Text">
            </form>
        </div>

    </div>
    <?php
}


/**
 * Listens for the export button submission and triggers the CSV download.
 */
function bmmm_listen_for_export_request() {
    // Check if our export button was clicked and the security nonce is valid.
    if ( isset( $_POST['bmmm_export_csv'] ) && check_admin_referer( 'bmmm_export_action', 'bmmm_export_nonce' ) ) {
        bmmm_generate_and_download_csv();
    }
}
add_action( 'admin_init', 'bmmm_listen_for_export_request' );


/**
 * Queries the media library, generates a CSV, and forces a browser download.
 */
function bmmm_generate_and_download_csv() {
    // Set the filename for the download
    $filename = 'media_export_' . date('Y-m-d') . '.csv';

    // Set the HTTP headers to tell the browser to download the file
    header( 'Content-Type: text/csv; charset=utf-g' );
    header( 'Content-Disposition: attachment; filename="' . $filename . '";' );

    // Get all images from the media library
    $args = array(
        'post_type'      => 'attachment',
        'post_mime_type' => 'image',
        'post_status'    => 'inherit',
        'posts_per_page' => -1, // -1 means retrieve ALL posts
    );
    $attachments = get_posts( $args );

    // Open a special PHP output stream that writes directly to the browser
    $output = fopen( 'php://output', 'w' );

    // Add the header row to the CSV file
    fputcsv( $output, array( 'image_url', 'title', 'alt_text' ) );

    // Loop through each attachment (image)
    if ( $attachments ) {
        foreach ( $attachments as $attachment ) {
            $image_url = wp_get_attachment_url( $attachment->ID );
            $title     = get_the_title( $attachment->ID );
            $alt_text  = get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true );

            // Add the data for the current image as a new row in the CSV
            fputcsv( $output, array( $image_url, $title, $alt_text ) );
        }
    }

    // Close the stream
    fclose( $output );
    // Stop the script from continuing to load the rest of the page
    exit();
}


/**
 * Listens for the import button submission and handles the CSV file processing.
 */
function bmmm_listen_for_import_request() {
    if ( isset( $_POST['bmmm_import_csv'] ) && check_admin_referer( 'bmmm_import_action', 'bmmm_import_nonce' ) && ! empty( $_FILES['bmmm_csv_file'] ) ) {

        if ( $_FILES['bmmm_csv_file']['error'] !== UPLOAD_ERR_OK ) {
            return; // Handle error
        }

        $csv_file_path = $_FILES['bmmm_csv_file']['tmp_name'];
        
        // Check if the dry run checkbox was ticked.
        $is_dry_run = isset( $_POST['bmmm_dry_run'] );

        // Pass the dry run status to the processing function.
        bmmm_process_csv_file( $csv_file_path, $is_dry_run );
    }
}
add_action( 'admin_init', 'bmmm_listen_for_import_request' );


/**
 * Processes the uploaded CSV file row by row.
 *
 * @param string $csv_file_path The temporary server path to the uploaded CSV file.
 */
function bmmm_process_csv_file( $csv_file_path, $is_dry_run = false ) {
    // Counters for the final report
    $updated_count = 0;
    $not_found_count = 0;
    $skipped_count = 0;

    $handle = fopen( $csv_file_path, 'r' );
    if ( ! $handle ) { return; }

    $headers = fgetcsv( $handle );
    $headers = array_map( 'trim', $headers );
    $url_index  = array_search( 'image_url', $headers );
    $title_index = array_search( 'title', $headers );
    $alt_index   = array_search( 'alt_text', $headers );

    if ( $url_index === false ) {
        fclose( $handle );
        return;
    }

    while ( ( $row = fgetcsv( $handle ) ) !== false ) {
        $original_url = trim( $row[ $url_index ] );

        if ( empty( $original_url ) ) {
            $skipped_count++;
            continue;
        }

        $attachment_id = 0;
        // ... (Smart URL Matching logic remains the same)
        $possible_urls = array_unique([
            $original_url,
            str_replace('/assets/', '/wp-content/uploads/', $original_url),
            str_replace('/storage/', '/wp-content/uploads/', $original_url)
        ]);
        
        foreach ($possible_urls as $url_to_try) {
            $id = attachment_url_to_postid( $url_to_try );
            if ( $id ) {
                $attachment_id = $id;
                break;
            }
        }

        if ( $attachment_id ) {
            // --- CORE DRY RUN LOGIC ---
            // Only run the database updates if this is NOT a dry run.
            if ( ! $is_dry_run ) {
                if ( $title_index !== false && isset( $row[ $title_index ] ) ) {
                    $title = trim( $row[ $title_index ] );
                    wp_update_post( ['ID' => $attachment_id, 'post_title' => $title] );
                }
                if ( $alt_index !== false && isset( $row[ $alt_index ] ) ) {
                    $alt_text = trim( $row[ $alt_index ] );
                    update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt_text );
                }
            }
            // We increment the counter regardless, to show what would have been updated.
            $updated_count++;
        } else {
            $not_found_count++;
        }
    }

    fclose( $handle );

    // Store the results AND the dry run status in the transient
    $results = array(
        'updated'    => $updated_count,
        'not_found'  => $not_found_count,
        'skipped'    => $skipped_count,
        'is_dry_run' => $is_dry_run, // Add this line
    );
    set_transient( 'bmmm_import_results', $results, 60 );
}


/**
 * Displays an admin notice with the results of the CSV import.
 */
function bmmm_display_import_notice() {
    $results = get_transient( 'bmmm_import_results' );
    if ( false === $results ) { return; }

    $is_dry_run  = isset( $results['is_dry_run'] ) ? (bool) $results['is_dry_run'] : false;
    $action_type = isset( $results['action_type'] ) ? $results['action_type'] : 'import'; // Default to 'import'

    $message = '';
    $notice_class = 'notice-info'; // Default to blue

    if ( $action_type === 'import' ) {
        // --- Build Import Message ---
        $updated_count   = (int) $results['updated'];
        $not_found_count = (int) $results['not_found'];
        $skipped_count   = (int) $results['skipped'];

        if ( $is_dry_run ) {
            $message .= '<strong>Dry Run Preview Complete:</strong><br>';
            $message .= "Images that would be updated: <strong>{$updated_count}</strong>.<br>";
        } else {
            $message .= '<strong>Live Import Complete:</strong><br>';
            $message .= "Successfully updated: <strong>{$updated_count}</strong> images.<br>";
            $notice_class = 'notice-success';
        }
        if ( $not_found_count > 0 ) { $message .= "Could not find: <strong>{$not_found_count}</strong> images.<br>"; $notice_class = 'notice-warning'; }
        if ( $skipped_count > 0 ) { $message .= "Skipped: <strong>{$skipped_count}</strong> rows.<br>"; }

    } elseif ( $action_type === 'generate' ) {
        // --- Build Generate Message ---
        $generated_count = (int) $results['generated'];

        if ( $is_dry_run ) {
            $message .= '<strong>Dry Run Preview Complete:</strong><br>';
            $message .= "Alt text would be generated for: <strong>{$generated_count}</strong> images.<br>";
        } else {
            $message .= '<strong>Generation Complete:</strong><br>';
            $message .= "Successfully generated alt text for: <strong>{$generated_count}</strong> images.<br>";
            $notice_class = 'notice-success';
        }
    }
    
    if ( $is_dry_run ) {
        $message .= "<em>No changes were saved to the database.</em>";
    }

    // Display the notice
    printf( '<div class="notice %s is-dismissible"><p>%s</p></div>', esc_attr( $notice_class ), $message );

    // Clean up
    delete_transient( 'bmmm_import_results' );
}
add_action( 'admin_notices', 'bmmm_display_import_notice' );


/**
 * Listens for the generate button submission and triggers the alt text generation.
 */
function bmmm_listen_for_generate_request() {
    if ( isset( $_POST['bmmm_generate_alt_text'] ) && check_admin_referer( 'bmmm_generate_action', 'bmmm_generate_nonce' ) ) {
        
        $template      = sanitize_text_field( $_POST['bmmm_alt_template'] );
        $target_option = sanitize_key( $_POST['bmmm_target_option'] ); // 'missing' or 'all'
        $is_dry_run    = isset( $_POST['bmmm_dry_run'] );

        // Basic validation
        if ( empty( $template ) || ! in_array( $target_option, ['missing', 'all'] ) ) {
            // If the template is empty or the target option is invalid, do nothing.
            return;
        }

        bmmm_process_alt_text_generation( $template, $target_option, $is_dry_run );
    }
}
add_action( 'admin_init', 'bmmm_listen_for_generate_request' );

/**
 * Processes the automatic generation of alt text based on user settings.
 *
 * @param string $template      The user-defined template string.
 * @param string $target_option 'missing' or 'all'.
 * @param bool   $is_dry_run    If true, no changes will be saved.
 */
function bmmm_process_alt_text_generation( $template, $target_option, $is_dry_run ) {
    $generated_count = 0;

    // --- Efficient Database Query ---
    $args = array(
        'post_type'      => 'attachment',
        'post_mime_type' => 'image',
        'post_status'    => 'inherit',
        'posts_per_page' => -1,
    );

    // If we are only targeting missing alt text, modify the query to be highly efficient.
    if ( $target_option === 'missing' ) {
        $args['meta_query'] = array(
            'relation' => 'OR',
            array(
                'key'     => '_wp_attachment_image_alt',
                'compare' => 'NOT EXISTS',
            ),
            array(
                'key'   => '_wp_attachment_image_alt',
                'value' => '',
            ),
        );
    }
    $attachments = get_posts( $args );
    // --- End of Query ---

    $site_name = get_bloginfo( 'name' );

    foreach ( $attachments as $attachment ) {
        // Hydrate the template with real data
        $replacements = array(
            '[Image Title]' => get_the_title( $attachment->ID ),
            '[Filename]'    => wp_basename( get_attached_file( $attachment->ID ) ),
            '[Site Name]'   => $site_name,
        );
        $new_alt_text = str_replace( array_keys( $replacements ), array_values( $replacements ), $template );

        if ( ! $is_dry_run ) {
            update_post_meta( $attachment->ID, '_wp_attachment_image_alt', $new_alt_text );
        }
        $generated_count++;
    }

    // Store results in a transient for the notice
    $results = array(
        'action_type' => 'generate', // Identify the action
        'generated'   => $generated_count,
        'is_dry_run'  => $is_dry_run,
    );
    set_transient( 'bmmm_import_results', $results, 60 );
}