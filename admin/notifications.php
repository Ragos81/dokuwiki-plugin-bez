<?php
/**
 * All DokuWiki plugins to extend the admin function
 * need to inherit from this class
 */

// must be run within DokuWiki
if (!defined('DOKU_INC')) die();

require_once DOKU_PLUGIN . 'bez/cron-functions.php';

class admin_plugin_bez_notifications extends DokuWiki_Admin_Plugin {
    
    
    function getMenuText($language) {
        return '[BEZ] Wyślij notyfikację';
    }
    
    /**
     * handle user request
     */
    private $mails = array();
    function handle() {
        global $auth, $conf, $bezlang;
        
        //inicialize lang array
        $this->getLang('bez');
        
        if (count($_POST) === 0)
            return; // first time - nothing to do
        if (!checkSecurityToken())
            return;
        //importuj
        if (isset($_POST['send'])) {
            $simulate = false;
            if (isset($_POST['simulate'])) {
                $simulate = true;
            }
            $http = 'http';
            if ($_SERVER['HTTPS'] === 'on') {
                $http = 'https';
            }
            $helper = $this->loadHelper('bez');
            $bezlang = $this->lang;
            $this->mails = send_message($_SERVER['SERVER_NAME'], $http, $conf, $helper, $auth, $simulate);
        }
    }
    /**
     * output appropriate html
     */
    function html() {
        ptln('<h1>' . $this->getMenuText('pl') . '</h1>');
        ptln('<form action="' . wl($ID) . '" method="post">');
        ptln('  <input type="hidden" name="do"   value="admin" />');
        ptln('  <input type="hidden" name="page" value="bez_notifications" />');
        ptln('  <label><input type="checkbox" checked name="simulate" /> Symuluj (nie wysyłaj wiadomości, tylko wygeneruj raport)</label><br><br>');
        formSecurityToken();
        ptln('  <input type="submit" name="send"  value="Wyślij powiadomienia" />');
        ptln('</form>');
        $log = array_reduce($this->mails, function($carry, $mail) {
            list($to, $subject, $body, $headers) = $mail;
            $carry .= '<div class="bez-mail-info">';
            $carry .= '<h1>Mail send to: ' . htmlspecialchars($to) . '</h1>';
            $carry .= '<div class="content">' . $body . '</div>';
            $carry .= '</div>';
            return $carry;
        }, '');
        ptln($log);
    }
    
}
