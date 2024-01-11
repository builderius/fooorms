<?php

namespace Fooorms\ACF;

class Admin_Smtp
{

    function __construct()
    {
        add_filter('fooorms/acf/form/settings_fields', [$this, 'email_acf_fields'], 10, 1);
        add_action('fooorms_smtp_mail', [$this, 'send_email_with_smtp'], 10, 7);
    }

    /**
     * @param $smtps
     * @param $from
     * @param $to
     * @param $subject
     * @param $message
     * @param $headers
     * @param $attachments
     * @return bool
     */
    function send_email_with_smtp($smtps, $from, $to, $subject, $message, $headers, $attachments)
    {
        if (empty($smtps)) {
            return false;
        }

        global $phpmailer;

        // (Re)create it, if it's gone missing.
        if (!($phpmailer instanceof \PHPMailer\PHPMailer\PHPMailer)) {
            require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
            require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
            require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
            $phpmailer = new \PHPMailer\PHPMailer\PHPMailer(true);

            $phpmailer::$validator = static function ($email) {
                return (bool)is_email($email);
            };
        }

        $results = [];

        foreach ($smtps as $smtp) {
            $results[] = $this->use_phpmailer($smtp, $from, $to, $subject, $message, $headers, $attachments);
        }

        return !in_array(false, $results);
    }

    /**
     * @param $smtp
     * @param $from
     * @param $to
     * @param $subject
     * @param $message
     * @param $headers
     * @param $attachments
     * @return bool
     * @global \PHPMailer\PHPMailer\PHPMailer $phpmailer
     *
     */
    function use_phpmailer($smtp, $from, $to, $subject, $message, $headers, $attachments)
    {
        global $phpmailer;

        try {
            // Empty out the values that may be set.
            $phpmailer->clearAllRecipients();
            $phpmailer->clearAttachments();
            $phpmailer->clearCustomHeaders();
            $phpmailer->clearReplyTos();
            $phpmailer->Body    = '';
            $phpmailer->AltBody = '';

            // Set actual values
            $phpmailer->Subject = $subject;
            $phpmailer->Body    = $message;

            $from_name  = '';
            $from_email = '';

            if (preg_match('/(.*)<(.+)>/', $from, $matches)) {
                if (count($matches) === 3) {
                    $from_name  = $matches[1];
                    $from_email = $matches[2];
                }
            } else {
                $from_email = $from;
            }

            if (is_array($to)) {
                $to = $to[0];
            }

            $phpmailer->setFrom($from_email, $from_name, false);
            $phpmailer->addAddress($to);
            $phpmailer->isHTML(true);
            $content_type           = 'text/html';
            $phpmailer->ContentType = $content_type;
            $charset                = get_bloginfo('charset');
            $phpmailer->CharSet     = apply_filters('wp_mail_charset', $charset);

            // Set custom headers.
            if (!empty($headers)) {
                foreach ((array)$headers as $name => $content) {
                    // Only add custom headers not added automatically by PHPMailer.
                    if (!in_array($name, array('MIME-Version', 'X-Mailer'), true)) {
                        try {
                            $phpmailer->addCustomHeader(sprintf('%1$s: %2$s', $name, $content));
                        } catch (\PHPMailer\PHPMailer\Exception $e) {
                            continue;
                        }
                    }
                }

                if (false !== stripos($content_type, 'multipart') && !empty($boundary)) {
                    $phpmailer->addCustomHeader(sprintf('Content-Type: %s; boundary="%s"', $content_type, $boundary));
                }
            }

            if (!empty($attachments)) {
                foreach ($attachments as $filename => $attachment) {
                    $filename = is_string($filename) ? $filename : '';

                    try {
                        $phpmailer->addAttachment($attachment, $filename);
                    } catch (\PHPMailer\PHPMailer\Exception $e) {
                        continue;
                    }
                }
            }

            // Set to use SMTP
            $phpmailer->isSMTP();
            $phpmailer->Host = $smtp['smtp_host'];
            $phpmailer->Port = $smtp['smtp_port'];

            if (!empty($smtp['smtp_username'])) {
                $phpmailer->SMTPAuth   = true;
                $phpmailer->SMTPSecure = "starttls";

                $phpmailer->Username = $smtp['smtp_username'];
                $phpmailer->Password = $smtp['smtp_password'];
            } else {
                $phpmailer->SMTPAuth = false;
            }

            $send = $phpmailer->send();

            return $send;
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            FooormsInit()->set_smtp_log($e->getMessage());

            return false;
        }
    }

    /**
     * @param $field_group
     * @return mixed|void
     */
    function email_acf_fields($field_group)
    {
        $field_group['fields'][] = array(
            'key'               => 'field_form_smtp_tab',
            'label'             => '<span class="dashicons dashicons-email-alt"></span>' . __(
                    'SMTP settings',
                    'fooorms'
                ),
            'name'              => '',
            'type'              => 'tab',
            'instructions'      => '',
            'required'          => 0,
            'conditional_logic' => 0,
            'wrapper'           => array(
                'width' => '',
                'class' => '',
                'id'    => '',
            ),
            'placement'         => 'left',
            'endpoint'          => 0,
        );

        $field_group['fields'][] = array(
            'key'               => 'field_form_smtp_items',
            'label'             => __('SMTP settings', 'fooorms'),
            'name'              => 'fooorms_smtp_items',
            'type'              => 'repeater',
            'instructions'      => __('Custom SMTP servers to be used while sending emails for this form.', 'fooorms'),
            'required'          => 0,
            'conditional_logic' => 0,
            'wrapper'           => array(
                'width' => '',
                'class' => '',
                'id'    => '',
            ),
            'collapsed'         => 'field_form_smtp_host',
            'min'               => '',
            'max'               => '',
            'layout'            => 'block',
            'button_label'      => __('Add new SMTP', 'fooorms'),
            'sub_fields'        => array(
                array(
                    'key'               => 'field_form_smtp_host',
                    'label'             => __('Host', 'fooorms'),
                    'name'              => 'smtp_host',
                    'type'              => 'text',
                    'instructions'      => '',
                    'required'          => 1,
                    'conditional_logic' => 0,
                    'wrapper'           => array(
                        'width' => '66',
                        'class' => '',
                        'id'    => '',
                    ),
                    'default_value'     => '',
                    'placeholder'       => 'Host URL',
                    'prepend'           => '',
                    'append'            => '',
                    'maxlength'         => '',
                ),
                array(
                    'key'               => 'field_form_smtp_active',
                    'label'             => __('Active?', 'fooorms'),
                    'name'              => 'smtp_active',
                    'type'              => 'true_false',
                    'instructions'      => '',
                    'required'          => 0,
                    'conditional_logic' => 0,
                    'wrapper'           => array(
                        'width' => '33',
                        'class' => '',
                        'id'    => '',
                    ),
                    'message'           => 'Yes',
                    'default_value'     => 1,
                ),
                array(
                    'key'               => 'field_form_smtp_port',
                    'label'             => __('Port', 'fooorms'),
                    'name'              => 'smtp_port',
                    'type'              => 'text',
                    'instructions'      => '',
                    'required'          => 1,
                    'conditional_logic' => 0,
                    'wrapper'           => array(
                        'width' => '',
                        'class' => '',
                        'id'    => '',
                    ),
                    'default_value'     => '25',
                    'placeholder'       => 'Port number',
                    'prepend'           => '',
                    'append'            => '',
                    'maxlength'         => '',
                ),
                array(
                    'key'               => 'field_form_smtp_username',
                    'label'             => __('Username', 'fooorms'),
                    'name'              => 'smtp_username',
                    'type'              => 'text',
                    'instructions'      => '',
                    'required'          => 1,
                    'conditional_logic' => 0,
                    'wrapper'           => array(
                        'width' => '',
                        'class' => '',
                        'id'    => '',
                    ),
                    'default_value'     => '',
                    'placeholder'       => 'Username',
                    'prepend'           => '',
                    'append'            => '',
                    'maxlength'         => '',
                ),
                array(
                    'key'               => 'field_form_smtp_password',
                    'label'             => __('Password', 'fooorms'),
                    'name'              => 'smtp_password',
                    'type'              => 'password',
                    'instructions'      => '',
                    'required'          => 1,
                    'conditional_logic' => 0,
                    'wrapper'           => array(
                        'width' => '',
                        'class' => '',
                        'id'    => '',
                    ),
                    'default_value'     => '',
                    'placeholder'       => 'Password',
                    'prepend'           => '',
                    'append'            => '',
                    'maxlength'         => '',
                )
            ),
        );

        $field_group = apply_filters('fooorms/acf/form/smtp_settings_fields', $field_group);

        return $field_group;
    }
}