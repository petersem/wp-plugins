<?php
/*
* Plugin Name: Contact Form
* Description: Custom contact form with full client-side + server-side validation, sticky values, and admin management.
* Version: 1.3
* Author: Matt
*/

// stop plugin from being executed outside of WP
if (!defined('ABSPATH')) exit;

// Activation: Create wp_feedback table
register_activation_hook(__FILE__, 'cf_feedback_install');

function cf_feedback_install()
{
    global $wpdb;

    $table = $wpdb->prefix . 'feedback';
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        first_name VARCHAR(40) NOT NULL,
        last_name VARCHAR(40) NOT NULL,
        contact_type VARCHAR(10) NOT NULL,
        email VARCHAR(120) NOT NULL,
        phone VARCHAR(40),
        best_day DATE,
        time_range VARCHAR(20),
        subject VARCHAR(60) NOT NULL,
        notes VARCHAR(200) NOT NULL,
        called TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Shortcode: Contact Form
add_shortcode('contact_form', 'cf_contact_form_render');

function cf_contact_form_render()
{

    $errors = [];
    $values = [
        'first_name' => '',
        'last_name' => '',
        'email' => '',
        'contact_type' => '',
        'phone' => '',
        'best_day' => '',
        'time_range' => '',
        'subject' => '',
        'notes' => ''
    ];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cf_submit'])) {
        $result = cf_feedback_process();
        $errors = $result['errors'];
        $values = $result['values'];
    }

    ob_start(); ?>

    <style>
        :root {
            --wp--style--block-gap: 0 !important;
        }

        .wp-block,
        .wp-block * {
            margin: 0 !important;
            padding: 0 !important;
        }

        .ast-container,
        .entry-content {
            padding: 0 !important;
        }

        #cfContactForm label.required::after {
            content: " *";
            color: red;
            font-weight: bold;
        }

        #cfContactForm label.phone-required {
            display: none;
        }

        #cfContactForm {
            max-width: 420px;
            margin: 0 !important;
            padding: 0 !important;
        }

        #cfContactForm label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            margin: 4px 0 2px !important;
        }

        #cfContactForm input,
        #cfContactForm select,
        #cfContactForm textarea {
            width: 100%;
            padding: 3px 5px !important;
            margin: 0 !important;
            border: 1px solid #ccc;
            font-size: 12px;
            box-sizing: border-box;
        }

        #cfContactForm .invalid {
            border: 1px solid red !important;
        }

        #cfContactForm .error-msg {
            color: red;
            font-size: 11px;
            margin: 2px 0 4px 0 !important;
        }

        #cfContactForm textarea {
            min-height: 50px !important;
        }

        #cfContactForm button {
            margin-top: 8px !important;
            padding: 6px 12px !important;
            font-size: 12px;
        }

        #phoneExtra {
            margin-top: 4px !important;
        }
    </style>

    <form id="cfContactForm" method="post" novalidate>
        <input type="hidden" name="cf_submit" value="1">
        <?php wp_nonce_field('cf_feedback_nonce', 'cf_nonce'); ?>
        <!-- FIRST NAME -->
        <label class="required">First Name</label>
        <input type="text" name="first_name"
            value="<?php echo esc_attr($values['first_name']); ?>"
            data-field="first_name"
            class="<?php echo isset($errors['first_name']) ? 'invalid' : ''; ?>">
        <?php if (isset($errors['first_name'])): ?>
            <div class="error-msg"><?php echo $errors['first_name']; ?></div>
        <?php endif; ?>

        <!-- LAST NAME -->
        <label class="required">Last Name</label>
        <input type="text" name="last_name"
            value="<?php echo esc_attr($values['last_name']); ?>"
            data-field="last_name"
            class="<?php echo isset($errors['last_name']) ? 'invalid' : ''; ?>">
        <?php if (isset($errors['last_name'])): ?>
            <div class="error-msg"><?php echo $errors['last_name']; ?></div>
        <?php endif; ?>

        <!-- EMAIL -->
        <label class="required">Email</label>
        <input type="email" name="email"
            value="<?php echo esc_attr($values['email']); ?>"
            data-field="email"
            class="<?php echo isset($errors['email']) ? 'invalid' : ''; ?>">
        <?php if (isset($errors['email'])): ?>
            <div class="error-msg"><?php echo $errors['email']; ?></div>
        <?php endif; ?>

        <!-- CONTACT TYPE -->
        <label class="required">Contact Type</label>
        <select name="contact_type" id="contact_type"
            data-field="contact_type"
            class="<?php echo isset($errors['contact_type']) ? 'invalid' : ''; ?>">
            <option value="">Select…</option>
            <option value="email" <?php selected($values['contact_type'], 'email'); ?>>Email</option>
            <option value="phone" <?php selected($values['contact_type'], 'phone'); ?>>Phone</option>
        </select>
        <?php if (isset($errors['contact_type'])): ?>
            <div class="error-msg"><?php echo $errors['contact_type']; ?></div>
        <?php endif; ?>

        <!-- PHONE FIELDS -->
        <div id="phoneExtra" style="display:none;">

            <label class="phone-required required">Phone</label>
            <input type="tel" name="phone" id="phone"
                value="<?php echo esc_attr($values['phone']); ?>"
                data-field="phone"
                class="<?php echo isset($errors['phone']) ? 'invalid' : ''; ?>">
            <?php if (isset($errors['phone'])): ?>
                <div class="error-msg"><?php echo $errors['phone']; ?></div>
            <?php endif; ?>

            <label class="phone-required required">Best Day</label>
            <input type="date" name="best_day"
                min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                value="<?php echo esc_attr($values['best_day']); ?>"
                data-field="best_day"
                class="<?php echo isset($errors['best_day']) ? 'invalid' : ''; ?>"
                required>
            <?php if (isset($errors['best_day'])): ?>
                <div class="error-msg"><?php echo $errors['best_day']; ?></div>
            <?php endif; ?>

            <label class="phone-required required">Best Time Range</label>
            <select name="time_range" id="time_range"
                data-field="time_range"
                class="<?php echo isset($errors['time_range']) ? 'invalid' : ''; ?>">
                <option value="">Select…</option>
                <option value="morning" <?php selected($values['time_range'], 'morning'); ?>>Morning</option>
                <option value="afternoon" <?php selected($values['time_range'], 'afternoon'); ?>>Afternoon</option>
                <option value="evening" <?php selected($values['time_range'], 'evening'); ?>>Evening</option>
            </select>
            <?php if (isset($errors['time_range'])): ?>
                <div class="error-msg"><?php echo $errors['time_range']; ?></div>
            <?php endif; ?>
        </div>

        <!-- SUBJECT -->
        <label class="required">Subject</label>
        <input type="text" name="subject"
            value="<?php echo esc_attr($values['subject']); ?>"
            data-field="subject"
            class="<?php echo isset($errors['subject']) ? 'invalid' : ''; ?>">
        <?php if (isset($errors['subject'])): ?>
            <div class="error-msg"><?php echo $errors['subject']; ?></div>
        <?php endif; ?>

        <!-- NOTES -->
        <label class="required">Notes</label>
        <textarea name="notes"
            data-field="notes"
            class="<?php echo isset($errors['notes']) ? 'invalid' : ''; ?>"><?php
                                                                            echo esc_textarea($values['notes']);
                                                                            ?></textarea>
        <?php if (isset($errors['notes'])): ?>
            <div class="error-msg"><?php echo $errors['notes']; ?></div>
        <?php endif; ?>

        <button type="submit">Send</button>

    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const rules = {
                first_name: {
                    regex: /^[A-Za-z]{2,40}$/,
                    msg: "Must be 2–40 letters."
                },
                last_name: {
                    regex: /^[A-Za-z]{2,40}$/,
                    msg: "Must be 2–40 letters."
                },
                email: {
                    regex: /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/,
                    msg: "Invalid email format."
                },
                contact_type: {
                    validate: v => v !== "",
                    msg: "Select a contact type."
                },
                phone: {
                    regex: /^(?=(?:\D*\d){8,11}$)(?:\+61|61|0)?[0-9 ]+$/,
                    msg: "Invalid phone number."
                },
                best_day: {
                    validate: v => {
                        if (!v) return false;

                        const today = new Date();
                        today.setHours(0, 0, 0, 0);

                        const tomorrow = new Date(today);
                        tomorrow.setDate(tomorrow.getDate() + 1);

                        const chosen = new Date(v);
                        chosen.setHours(0, 0, 0, 0);

                        return chosen >= tomorrow;
                    },
                    msg: "Date must be tomorrow or later."
                },
                time_range: {
                    validate: v => v !== "",
                    msg: "Select a time range."
                },
                subject: {
                    validate: v => v.trim() !== "",
                    msg: "Subject is required."
                },
                notes: {
                    validate: v => v.trim() !== "",
                    msg: "Notes are required."
                }
            };

            const form = document.getElementById('cfContactForm');

            function showError(field, message) {
                field.classList.add('invalid');
                let next = field.nextElementSibling;
                if (!next || !next.classList.contains('error-msg')) {
                    next = document.createElement('div');
                    next.className = 'error-msg';
                    field.insertAdjacentElement('afterend', next);
                }
                next.textContent = message;
            }

            function clearError(field) {
                field.classList.remove('invalid');
                let next = field.nextElementSibling;
                if (next && next.classList.contains('error-msg')) next.remove();
            }

            function validateField(name) {
                const field = form.querySelector(`[data-field="${name}"]`);
                const value = field.value.trim();
                const rule = rules[name];

                let valid = rule.regex ? rule.regex.test(value) : rule.validate(value);

                if (!valid) showError(field, rule.msg);
                else clearError(field);

                return valid;
            }

            form.querySelectorAll('[data-field]').forEach(field => {
                field.addEventListener('blur', () => validateField(field.dataset.field));
            });

            const contactType = document.getElementById('contact_type');
            const phoneExtra = document.getElementById('phoneExtra');
            const phoneRequiredLabels = document.querySelectorAll('.phone-required');

            function updatePhoneVisibility() {
                if (contactType.value === 'phone') {
                    phoneExtra.style.display = 'block';
                    phoneRequiredLabels.forEach(el => el.style.display = 'block');
                } else {
                    phoneExtra.style.display = 'none';
                    phoneRequiredLabels.forEach(el => el.style.display = 'none');
                }
            }

            contactType.addEventListener('change', updatePhoneVisibility);
            updatePhoneVisibility();

            form.addEventListener('submit', function(e) {
                let valid = true;

                Object.keys(rules).forEach(name => {
                    if (['phone', 'best_day', 'time_range'].includes(name) && contactType.value !== 'phone') return;
                    if (!validateField(name)) valid = false;
                });

                if (!valid) e.preventDefault();
                
            });

        });
    </script>

<?php
    return ob_get_clean();
}

// Process Form Submission → Save to DB
function cf_feedback_process()
{

    $errors = [];
    $values = [];

    foreach ($_POST as $key => $val) {
        $values[$key] = sanitize_text_field($val);
    }

    if (!isset($_POST['cf_nonce']) || !wp_verify_nonce($_POST['cf_nonce'], 'cf_feedback_nonce')) {
        $errors['general'] = "Security validation failed.";
        return ['errors' => $errors, 'values' => $values];
    }

    if (empty($values['first_name']) || !preg_match('/^[A-Za-z]{2,40}$/', $values['first_name'])) {
        $errors['first_name'] = "Must be 2–40 letters.";
    }

    if (empty($values['last_name']) || !preg_match('/^[A-Za-z]{2,40}$/', $values['last_name'])) {
        $errors['last_name'] = "Must be 2–40 letters.";
    }

    if (
        empty($values['email']) ||
        !preg_match('/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/', $values['email'])
    ) {
        $errors['email'] = "Invalid email format.";
    }

    if (empty($values['contact_type'])) {
        $errors['contact_type'] = "Select a contact type.";
    }

    if ($values['contact_type'] === 'phone') {

        if (
            empty($values['phone']) ||
            !preg_match('/^(?=(?:\D*\d){8,11}$)(?:\+61|61|0)?[0-9 ]+$/', $values['phone'])
        ) {
            $errors['phone'] = "Invalid phone number.";
        }

        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        if (empty($values['best_day']) || $values['best_day'] < $tomorrow) {
            $errors['best_day'] = "Date must be tomorrow or later.";
        }

        if (empty($values['time_range'])) {
            $errors['time_range'] = "Select a time range.";
        }
    }

    if (empty($values['subject'])) {
        $errors['subject'] = "Subject is required.";
    }

    if (empty($values['notes'])) {
        $errors['notes'] = "Notes are required.";
    }

    if (!empty($errors)) {
        return ['errors' => $errors, 'values' => $values];
    }

    global $wpdb;
    $table = $wpdb->prefix . 'feedback';

    $wpdb->insert($table, [
        'first_name'   => $values['first_name'],
        'last_name'    => $values['last_name'],
        'contact_type' => $values['contact_type'],
        'email'        => $values['email'],
        'phone'        => $values['phone'],
        'best_day'     => $values['best_day'],
        'time_range'   => $values['time_range'],
        'subject'      => $values['subject'],
        'notes'        => $values['notes'],
        'called'       => 0
    ]);

    echo "<h4 style='color: blue;'>Thank you — your message has been sent.</h4>";

    return ['errors' => [], 'values' => $values];
}

// Admin Menu
add_action('admin_menu', 'cf_feedback_admin_menu');

function cf_feedback_admin_menu()
{
    add_menu_page(
        'Feedback Processing',
        'Feedback Processing',
        'manage_options',
        'cf-feedback',
        'cf_feedback_admin_page',
        'dashicons-email',
        26
    );
}

// Admin Page — View + Delete + Called + Pagination
function cf_feedback_admin_page()
{
    global $wpdb;
    $table = $wpdb->prefix . 'feedback';

    /* Mark as called */
    if (isset($_GET['called']) && is_numeric($_GET['called'])) {
        $id = intval($_GET['called']);
        $wpdb->update($table, ['called' => 1], ['id' => $id]);
        echo "<div class='updated'><p>Marked as ACTIONED.</p></div>";
    }

    /* Delete */
    if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
        $id = intval($_GET['delete']);
        $wpdb->delete($table, ['id' => $id]);
        echo "<div class='updated'><p>Entry deleted.</p></div>";
    }

    /* Show all checkbox */
    // Toggle show_all state
    if (isset($_GET['toggle'])) {
        $show_all = !(isset($_GET['show_all']) && $_GET['show_all'] == '1');
    } else {
        $show_all = isset($_GET['show_all']) && $_GET['show_all'] == '1';
    }
    $view_label = $show_all ? "Showing actioned and unactioned (all) records" : "Showing records requiring action";
    $button_label = $show_all ? "Show Feedback to action" : "Show All Records";

    // Persist the state
    $show_all_param = $show_all ? '&show_all=1' : '';

    echo '<div class="wrap"><h1>Feedback Processing</h1>';

    echo '<form method="get" style="margin-bottom:10px;">';
    echo '<input type="hidden" name="page" value="cf-feedback">';
    echo '<input type="hidden" name="show_all" value="' . ($show_all ? '1' : '0') . '">';
    echo '<input type="hidden" name="toggle" value="1">';
    echo '<button class="button">' . $button_label . '</button>';
    echo '</form>';
    echo '<span>' . $view_label . '</span>';

    /* Pagination setup */
    $per_page = 7;
    $page_num = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($page_num - 1) * $per_page;

    /* Show only uncalled unless "show_all" is checked */
    $where = $show_all ? "" : "WHERE called = 0";

    /* Total count for pagination */
    $total = $wpdb->get_var("SELECT COUNT(*) FROM $table $where");

    /* Fetch rows */
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $table $where ORDER BY created_at ASC LIMIT %d OFFSET %d",
            $per_page,
            $offset
        )
    );

    /* Table */
    echo '<table class="widefat fixed striped">
            <thead>
                <tr>
                    <th>Created</th>
                    <th>Name</th>
                    <th>Contact Type</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Best Day</th>
                    <th>Time Range</th>
                    <th>Subject</th>
                    <th>Notes</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>';

    if ($rows) {
        foreach ($rows as $r) {
            echo '<tr>
                    <td>' . esc_html($r->created_at) . '</td>
                    <td>' . esc_html($r->first_name . " " . $r->last_name) . '</td>
                    <td>' . esc_html($r->contact_type) . '</td>
                    <td>' . esc_html($r->email) . '</td>
                    <td>' . esc_html($r->phone) . '</td>
                    <td>' . esc_html($r->best_day) . '</td>
                    <td>' . esc_html($r->time_range) . '</td>
                    <td>' . esc_html($r->subject) . '</td>
                    <td>' . esc_html($r->notes) . '</td>
                    <td>';
            /* Called + Delete buttons */
            if ($r->called == 0) {
                echo '<a href="?page=cf-feedback' . $show_all_param . '&called=' . $r->id . '" 
          class="button button-small">Action</a> ';
            }

            echo '<a href="?page=cf-feedback' . $show_all_param . '&delete=' . $r->id . '" 
          onclick="return confirm(\'Delete this entry?\');"
          class="button button-small" style="color:red;">Delete</a>';

            echo '</td></tr>';
        }
    } else {
        echo '<tr><td colspan="11">No records found.</td></tr>';
    }

    echo '</tbody></table>';

    /* Pagination links */
    $total_pages = ceil($total / $per_page);

    if ($total_pages > 1) {
        echo '<div class="tablenav" style="width:100%; text-align:center;"><div class="tablenav-pages">';


        for ($i = 1; $i <= $total_pages; $i++) {
            $class = ($i == $page_num) ? ' class="page-numbers current"' : ' class="page-numbers"';
            $url = '?page=cf-feedback' . $show_all_param . '&paged=' . $i;

            if ($show_all) {
                $url .= '&show_all=1';
            }

            echo "<a href=\"$url\"$class>$i</a> ";
        }

        echo '</div></div>';
    }

    echo '<style>
    .tablenav-pages {
        text-align: center !important;
        font-size: 20px !important;
        padding: 12px 0;
    }
    .tablenav-pages .page-numbers {
        margin: 0 8px;
        padding: 6px 14px;
        font-size: 20px;
        display: inline-block;
    }
    .tablenav-pages .current {
        background: #007cba;
        color: #fff;
        border-radius: 4px;
    }
</style>';
}
