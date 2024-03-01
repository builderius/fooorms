<?php

namespace Fooorms;

class Mail extends \WP_Mail
{

    private $from = '';
    private $to = array();
    private $attachments = array();
    private $variables = array();
    private $templateHTML = '';
    private $smtps = array();

    public static function init()
    {
        return new Self;
    }

    /**
     * Set From header
     * @param String
     * @return Object $this
     */
    public function from($from, $variables = null)
    {
        $this->from = $from;

        if (is_array($variables)) {
            $this->variables = $variables;
        }

        return $this;
    }

    public function buildFrom()
    {
        return $this->parseAsMustache($this->from, $this->variables);
    }

    /**
     * Set recipients
     * @param Array|String $to
     * @return Object $this
     */
    public function to($to, $variables = null)
    {
        if (is_array($to)) {
            $this->to = $to;
        } else {
            $this->to = array($to);
        }

        if (is_array($variables)) {
            $this->variables = $variables;
        }

        return $this;
    }

    public function buildTo()
    {
        $newTo = [];
        foreach ($this->to as $email_to) {
            $newTo[] = $this->parseAsMustache($email_to, $this->variables);
        }

        return $newTo;
    }

    /**
     * Set email Subject
     * @param String $subject
     * @param Array $variables
     * @return Object $this
     */
    public function subject($subject, $variables = null)
    {
        $this->subject = $subject;

        if (is_array($variables)) {
            $this->variables = $variables;
        }

        return $this;
    }

    public function buildSubject()
    {
        return $this->parseAsMustache($this->subject, $this->variables);
    }

    /**
     * Builds Email Headers
     * @return String email headers
     */
    public function buildHeaders()
    {
        $headers = '';

        if (!empty($this->buildFrom())) {
            $headers .= sprintf("from: %s \r\n", $this->buildFrom());
        }

        return $headers;
    }

    /**
     * Set HTML template of email body
     * @param String $html
     * @param Array $variables
     * @return Object $this
     */
    public function templateHTML($html, $variables = null)
    {
        $this->templateHTML = $html;

        if (is_array($variables)) {
            $this->variables = $variables;
        }

        return $this;
    }

    /**
     * Attach a file or array of files.
     * Filepaths must be absolute.
     * @param String|Array $path
     * @return Object $this
     * @throws \Exception
     */
    public function attach($path)
    {
        if (is_array($path)) {
            $this->attachments = array();
            foreach ($path as $path_) {
                if (!file_exists($path_)) {
                    throw new \Exception("Attachment not found at $path");
                } else {
                    $this->attachments[] = $path_;
                }
            }
        } else {
            if (!file_exists($path)) {
                throw new \Exception("Attachment not found at $path");
            }
            $this->attachments = array($path);
        }

        return $this;
    }

    /**
     * Renders the template
     * @return String
     */
    public function render()
    {
        $variables = $this->variables;

        if (!is_array($variables) || empty($variables)) {
            return $this->templateHTML;
        }

        return $this->parseAsMustache($this->templateHTML, $variables);
    }

    /**
     * @return string
     */
    function send_as_html()
    {
        return 'text/html';
    }

    function setSMTP($data)
    {
        $this->smtps = $data;

        return $this;
    }

    /**
     * Sends a rendered email using
     * WordPress's wp_mail() function
     * @return Bool
     */
    public function send()
    {

        if (count($this->to) === 0) {
            throw new \Exception('You must set at least 1 recipient');
        }

        if (empty($this->templateHTML)) {
            throw new \Exception('You must set a template');
        }

        add_filter('wp_mail_content_type', [$this, 'send_as_html']);

        if (!empty($this->smtps)) {
            do_action(
                'fooorms_smtp_mail',
                $this->smtps,
                $this->buildFrom(),
                $this->buildTo(),
                $this->buildSubject(),
                $this->render(),
                $this->buildHeaders(),
                $this->attachments
            );

            $smtp_logs = FooormsInit()->get_smtp_log();

            if (empty($smtp_logs)) {
                return true;
            } else {
                return false;
            }
        }

        return wp_mail($this->buildTo(), $this->buildSubject(), $this->render(), $this->buildHeaders(), $this->attachments);
    }
}
