<?php

/**
 * Classes for online editing of text and config files.
 *
 * PHP versions 4 and 5
 *
 * @category  CMSimple_XH
 * @package   XH
 * @author    Peter Harteg <peter@harteg.dk>
 * @author    The CMSimple_XH developers <devs@cmsimple-xh.org>
 * @copyright 1999-2009 <http://cmsimple.org/>
 * @copyright 2009-2013 The CMSimple_XH developers <http://cmsimple-xh.org/?The_Team>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @version   SVN: $Id$
 * @link      http://cmsimple-xh.org/
 */


/**
 * The abstract base class for editing of text and config files.
 *
 * @category CMSimple_XH
 * @package  XH
 * @author   The CMSimple_XH developers <devs@cmsimple-xh.org>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://cmsimple-xh.org/

 * @abstract
 */
class XH_FileEdit
{
    /**
     * Additional POST parameters.
     *
     * @access protected
     * @var    array
     */
    var $params = array();

    /**
     * The name of the plugin.
     *
     * @access protected
     * @var    string
     */
    var $plugin = null;

    /**
     * @var string
     */
    var $caption = null;

    /**
     * The name of the file to edit.
     *
     * @access protected
     * @var    string
     */
    var $filename = null;

    /**
     * URL for redirecting after successful submission (PRG pattern).
     *
     * @access protected
     * @var    string
     */
    var $redir = null;

    /**
     * Saves the file. Returns whether that succeeded.
     *
     * @access protected
     * @return bool
     */
    function save()
    {
        // TODO: use XH_writeFile()
        $ok = is_writable($this->filename)
            && ($fh = fopen($this->filename, 'w'))
            && fwrite($fh, $this->asString()) !== false;
        if (!empty($fh)) {
            fclose($fh);
        }
        return $ok;
    }

    /**
     * Returns the form to edit the file contents.
     *
     * @abstract
     * @access public
     * @return string  (X)HTML.
     */
    function form()
    {
    }

    /**
     * Handles the form submission.
     *
     * If file could be successfully saved, triggers a redirect.
     * Otherwise writes error message to $e, and returns the edit form.
     *
     * @abstract
     * @access public
     * @return mixed  The (X)HTML resp. void.
     */
    function submit()
    {
    }

    /**
     * Returns the the file contents as string for saving.
     *
     * @abstract
     * @access protected
     * @return string
     */
    function asString()
    {
    }
}


/**
 * The abstract base class for editing of text files.
 *
 * @category CMSimple_XH
 * @package  XH
 * @author   The CMSimple_XH developers <devs@cmsimple-xh.org>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://cmsimple-xh.org/
 *
 * @abstract
 */
class XH_TextFileEdit extends XH_FileEdit
{
    /**
     * The name of the textarea.
     *
     * @var string
     */
    var $textareaName = null;

    /**
     * The contents of the file.
     *
     * @var string
     */
    var $text = null;

    /**
     * Construct an instance.
     */
    function XH_TextFileEdit()
    {
        // TODO: error handling
        $this->text = file_get_contents($this->filename);
    }

    /**
     * Returns the form to edit the file contents.
     *
     * @access public
     * @return string  (X)HTML.
     */
    function form()
    {
        global $tx;

        $action = isset($this->plugin) ? '?&amp;' . $this->plugin : '.';
        $value = ucfirst($tx['action']['save']); // TODO: ut8_ucfirst()
        $button = tag('input type="submit" class="submit" value="' . $value . '"');
        $o = '<h1>' . ucfirst($this->caption) . '</h1>'
            . '<form action="' . $action . '" method="POST">'
            . '<textarea rows="25" cols="80" name="' . $this->textareaName
            . '" class="cmsimplecore_file_edit">'
            . htmlspecialchars($this->text, ENT_QUOTES, 'UTF-8')
            . '</textarea>';
        foreach ($this->params as $param => $value) {
            $o .= tag(
                'input type="hidden" name="' . $param . '" value="' . $value . '"'
            );
        }
        $o .= $button . '</form>';
        return $o;
    }

    /**
     * Handles the form submission.
     *
     * If file could be successfully saved, triggers a redirect.
     * Otherwise writes error message to $e, and returns the edit form.
     *
     * @access public
     * @return mixed
     */
    function submit()
    {
        $this->text = stsl($_POST[$this->textareaName]);
        if ($this->save()) {
            header('Location: ' . $this->redir, true, 303);
            exit;
        } else {
            e('cntsave', 'file', $this->filename);
            return $this->form();
        }
    }

    /**
     * Returns the the file contents as string for saving.
     *
     * @access protected
     * @return string
     */
    function asString()
    {
        return $this->text;
    }
}


/**
 * Editing of core text files.
 *
 * @category CMSimple_XH
 * @package  XH
 * @author   The CMSimple_XH developers <devs@cmsimple-xh.org>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://cmsimple-xh.org/
 */
class XH_CoreTextFileEdit extends XH_TextFileEdit
{
    /**
     * Construct an instance.
     */
    function XH_CoreTextFileEdit()
    {
        global $pth, $file;

        $this->filename = $pth['file'][$file];
        $this->params = array('file' => $file, 'action' => 'save');
        $this->redir = "?file=$file&action=edit";
        $this->textareaName = 'text';
        parent::XH_TextFileEdit();
    }
}


/**
 * Editing of plugin text files.
 *
 * @category CMSimple_XH
 * @package  XH
 * @author   The CMSimple_XH developers <devs@cmsimple-xh.org>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://cmsimple-xh.org/
 */
class XH_PluginTextFileEdit extends XH_TextFileEdit
{
    /**
     * Construct an instance.
     */
    function XH_PluginTextFileEdit()
    {
        global $pth, $plugin;

        $this->plugin = $plugin;
        $this->filename = $pth['file']['plugin_stylesheet'];
        $this->params = array('admin' => 'plugin_stylesheet',
                              'action' => 'plugin_textsave');
        $this->redir = "?&$plugin&admin=plugin_stylesheet&action=plugin_text";
        $this->textareaName = 'plugin_text';
        $this->caption = $plugin;
        parent::XH_TextFileEdit();
    }
}


/**
 * The abstract base class for editing of config files.
 *
 * @category CMSimple_XH
 * @package  XH
 * @author   The CMSimple_XH developers <devs@cmsimple-xh.org>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://cmsimple-xh.org/
 *
 * @abstract
 */
class XH_ArrayFileEdit extends XH_FileEdit
{
    var $cfg = null;
    //var $file = null;
    //var $admin = null;
    //var $action = null;
    //var $plugin = null;

    /**
     * Construct an instance
     *
     * @todo constructor should probably be abstract
     */
    function XH_ArrayFileEdit()
    {
        $this->cfg = array(
            'general' => array(
                'one' => array(
                    'val' => 'hugo',
                    'hint' => 'This is the first option',
                    'type' => 'string'
                ),
                'two' => array(
                    'val' => 'Peter, Paul and Mary ...',
                    'hint' => 'This is the second option',
                    'type' => 'text'
                )
            ),
            'special' => array(
                'three' => array(
                    'val' => true,
                    'hint' => 'This is the third option',
                    'type' => 'bool'
                ),
                'four' => array(
                    'val' => 'two',
                    'hint' => 'This is the fourth option',
                    'type' => 'enum',
                    'vals' => array('one', 'two', 'three')
                )
            )
        );
    }

    /**
     * Returns a key split to category and rest.
     *
     * @param string $key The original key.
     *
     * @return array
     *
     * @access protected
     */
    function splitKey($key)
    {
        // TODO: use explode()'s $limit
        $parts = explode('_', $key);
        $first = array_shift($parts);
        return array($first, implode('_', $parts));
    }

    /**
     * Returns whether all options are hidden.
     *
     * @param array $options The list of options.
     *
     * @return bool
     */
    function hasVisibleFields($options)
    {
        foreach ($options as $opt) {
            if ($opt['type'] != 'hidden') {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns the "change password" dialog.
     *
     * @param string $iname The base name of the password input.
     *
     * @return string  The (X)HTML.
     *
     * @access private
     *
     * @todo: finish up
     * @todo: i18n
     */
    function passwordDialog($iname)
    {
        $id = $iname . '_DLG';
        $o = '<div id="' . $id . '" style="display:none">'
            . '<table style="width: 100%">'
            . '<tr><td>Old Password</td><td>'
            . tag(
                'input type="password" name="' . $iname
                . '_OLD" value="" class="cmsimplecore_settings"'
            )
            . '</td></tr>'
            . '<tr><td>New Password</td><td>'
            . tag(
                'input type="password" name="' . $iname
                . '_NEW" value="" class="cmsimplecore_settings"'
            )
            . '</td></tr>'
            . '<tr><td>Confirmation</td><td>'
            . tag(
                'input type="password" name="' . $iname
                . '_CONFIRM" value="" class="cmsimplecore_settings"'
            )
            . '</td></tr>'
            . '</table>'
            . '</div>';
        $onclick = 'var dlg = document.getElementById(\'' . $id
            . '\'); xh.modalDialog(dlg, \'350px\', xh.validatePassword)';
        $o .= '<button type="button" onclick="' . $onclick
            . '">Change Password</button>';
        return $o;
    }

    /**
     * Returns a form field.
     *
     * @param string $cat  The category.
     * @param string $name The name.
     * @param array  $opt  The field options.
     *
     * @return string The (X)HTML.
     *
     * @access private
     */
    function formField($cat, $name, $opt)
    {
        $iname = XH_FORM_NAMESPACE . $cat . '_' . $name;
        switch ($opt['type']) {
        case 'password':
            return $this->passwordDialog($iname);
        case 'text':
            $class = 'cmsimplecore_settings';
            if (utf8_strlen($opt['val']) < 30) {
                $class .= ' cmsimplecore_settings_short';
            }
            return '<textarea name="' . $iname . '" rows="3" cols="30"'
                . ' class="' . $class . '">'
                . htmlspecialchars($opt['val'], ENT_QUOTES, 'UTF-8')
                . '</textarea>';
        case 'bool':
            return tag(
                'input type="checkbox" name="' . $iname . '"'
                . ($opt['val'] ? ' checked="checked"' : '')
            );
        case 'enum':
            $o = '<select name="' . $iname . '">';
            foreach ($opt['vals'] as $val) {
                $sel = $val == $opt['val'] ? ' selected="selected"' : '';
                $o .= '<option' . $sel . '>' . $val . '</option>';
            }
            $o .= '</select>';
            return $o;
        case 'hidden':
            return tag(
                'input type="hidden" name="' . $iname . '" value="'
                . htmlspecialchars($opt['val'], ENT_QUOTES, 'UTF-8') . '"'
            );
        default:
            return tag(
                'input type="text" name="' . $iname . '" value="'
                . htmlspecialchars($opt['val'], ENT_QUOTES, 'UTF-8')
                . '" class="cmsimplecore_settings"'
            );
        }
    }

    /**
     * Returns the form to edit the file contents.
     *
     * @access public
     * @global array
     * @global array
     * @return string  (X)HTML.
     */
    function form()
    {
        global $pth, $tx;

        $action = isset($this->plugin) ? '?&amp;' . $this->plugin : '.';
        $value = ucfirst($tx['action']['save']); // TODO: utf8_ucfirst()
        $button = tag('input type="submit" class="submit" value="' . $value . '"');
        $o = '<h1>' . ucfirst($this->caption) . '</h1>'
            . '<form id="xh_config_form" action="' . $action
            . '" method="POST" accept-charset="UTF-8">'
            . $button
            . '<table style="width: 100%">';
        foreach ($this->cfg as $category => $options) {
            if ($this->hasVisibleFields($options)) {
                $o .= '<tr><td colspan="2"><h6>' . ucfirst($category)
                    . '</h6></td></tr>';
            }
            foreach ($options as $name => $opt) {
                $filename = $pth['folder']['flags'] . 'help_icon.png';
                $info = isset($opt['hint'])
                    ? '<div class="pl_tooltip">'
                        . tag('img src="' . $filename. '" alt=""')
                        . '<div>' . $opt['hint'] . '</div></div> '
                    : '';
                if ($opt['type'] == 'hidden') {
                    $o .= '<tr><td colspan="2">'
                        . $this->formField($category, $name, $opt);
                } else {
                    $o .= '<tr><td>' . $info . ucfirst($name) . '</td><td>'
                        . $this->formField($category, $name, $opt);
                }
                $o .= '</td></tr>';
            }
        }
        $o .= '</table>';
        foreach ($this->params as $param => $value) {
            $o .= tag(
                'input type="hidden" name="' . $param . '" value="' . $value . '"'
            );
        }
        $o .= $button . '</form>';
        return $o;
    }

    /**
     * Handles the form submission.
     *
     * Triggers a redirect, if the submission was valid
     * and the file could be successfully saved.
     * Otherwise writes an error message to $e, and returns the edit form.
     *
     * @access public
     *
     * @global string  Error messages.
     * @global object  The password hasher.
     * @return string  The (X)HTML.
     */
    function submit()
    {
        global $e, $xh_hasher;

        $errors = array();
        foreach ($this->cfg as $cat => $opts) {
            foreach ($opts as $name => $opt) {
                $iname = XH_FORM_NAMESPACE . $cat . '_' . $name;
                $val = isset($_POST[$iname]) ? stsl($_POST[$iname]) : '';
                if ($opt['type'] == 'bool') {
                    // TODO: which values should be written back?
                    $val = isset($_POST[$iname]) ? 'true' : '';
                } elseif ($opt['type'] == 'password') {
                    if (empty($_POST[$iname . '_OLD'])) {
                        $val = $opt['val'];
                    } else {
                        $old = stsl($_POST[$iname . '_OLD']);
                        $new = stsl($_POST[$iname . '_NEW']);
                        $confirm = stsl($_POST[$iname . '_CONFIRM']);
                        if (!$xh_hasher->CheckPassword($old, $opt['val'])) {
                            $errors[] = '<li>Wrong password</li>'; // TODO: i18n
                        } else {
                            if (empty($new)) {
                                // TODO: i18n
                                $errors[] = '<li>Password must not be empty</li>';
                            } elseif ($new != $confirm) {
                                // TODO: i18n
                                $errors[] = '<li>Passwords do not match</li>';
                            } else {
                                $val = $xh_hasher->HashPassword($new);
                            }
                        }
                    }
                }
                $this->cfg[$cat][$name]['val'] = $val;
            }
        }
        if (!empty($errors)) {
            $e .= implode('', $errors);
            return $this->form();
        } elseif ($this->save()) {
            header('Location: ' . $this->redir, true, 303);
            exit;
        } else {
            e('cntsave', 'file', $this->filename);
            return $this->form();
        }
    }
}


/**
 * The abstract base class for editing of core config and text files.
 *
 * @category CMSimple_XH
 * @package  XH
 * @author   The CMSimple_XH developers <devs@cmsimple-xh.org>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://cmsimple-xh.org/
 *
 * @abstract
 */
class XH_CoreArrayFileEdit extends XH_ArrayFileEdit
{
    /**
     * Constructs an instance.
     *
     * @global array  The paths of system files and folders.
     * @global string The key of the system file.
     * @global array  The localization of the plugins.
     */
    function XH_CoreArrayFileEdit()
    {
        global $pth, $file, $tx;

        $this->filename = $pth['file'][$file];
        $this->caption = utf8_ucfirst($tx['action']['edit']) . ' '
            . (isset($tx['filetype'][$file]) ? $tx['filetype'][$file] : $file);
    }

    /**
     * Returns the the file contents as string for saving.
     *
     * @access protected
     * @return string
     */
    function asString()
    {
        $o = "<?php\n\n";
        foreach ($this->cfg as $cat => $opts) {
            foreach ($opts as $name => $opt) {
                $opt = addcslashes($opt['val'], "\0..\37\"\$\\");
                $o .= "\$$this->varName['$cat']['$name']=\"$opt\";\n";
            }
        }
        $o .= "\n?>\n";
        return $o;
    }

    /**
     * Returns an array of select options for files.
     *
     * @param string $fn    The key of the system folder.
     * @param string $regex The regex the filename must match.
     *
     * @global array The paths of system files and folders.
     *
     * @return array
     */
    function selectOptions($fn, $regex)
    {
        global $pth;

        $options = array();
        if ($dh = opendir($pth['folder'][$fn])) {
            while (($p = readdir($dh)) !== false) {
                if (preg_match($regex, $p, $m)) {
                    $options[] = $m[1];
                }
            }
            closedir($dh);
        }
        natcasesort($options);
        return $options;
    }
}


/**
 * Editing of core config files.
 *
 * @category CMSimple_XH
 * @package  XH
 * @author   The CMSimple_XH developers <devs@cmsimple-xh.org>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://cmsimple-xh.org/
 */
class XH_CoreConfigFileEdit extends XH_CoreArrayFileEdit
{
    /**
     * Constructs an instance.
     *
     * @global array  The paths of system files and folders.
     * @global array  The configuration of the core.
     * @global array  The localization of the core.
     */
    function XH_CoreConfigFileEdit()
    {
        global $pth, $cf, $tx;

        parent::XH_CoreArrayFileEdit();
        $this->varName = 'cf';
        $this->params = array(
            'form' => 'array',
            'file' => 'config',
            'action' => 'save'
        );
        $this->redir = '?file=config&action=array';
        $this->cfg = array();
        $fn = $pth['folder']['cmsimple'] . 'metaconfig.php';
        if (is_readable($fn)) {
            include $fn;
        }
        foreach ($cf as $cat => $opts) {
            $this->cfg[$cat] = array();
            foreach ($opts as $name => $val) {
                // The following are there for backwards compatibility,
                // and have to be suppressed in the config form.
                if ($cat == 'scripting' && $name == 'regexp'
                    || $cat == 'site' && $name == 'title'
                ) {
                    continue;
                }
                $type = isset($mcf[$cat][$name]) ? $mcf[$cat][$name] : 'string';
                if (strpos($type, 'enum:') === 0) {
                    $vals = explode(',', substr($type, strlen('enum:')));
                    $type = 'enum';
                } else {
                    $vals = null;
                }
                $co = array('val' => $val, 'type' => $type, 'vals' => $vals);
                if (isset($tx['help']["${cat}_$name"])) {
                    $co['hint'] = $tx['help']["${cat}_$name"];
                }
                if ($cat == 'language' && $name == 'default') {
                    $co['type'] = 'enum';
                    $co['vals'] = $this->selectOptions(
                        'language', '/^([a-z]{2})\.php$/i'
                    );
                } elseif ($cat == 'site' && $name == 'template') {
                    $co['type'] = 'enum';
                    $co['vals'] = $this->selectOptions(
                        'templates', '/^([^\.]*)$/i'
                    );
                } elseif ($cat == 'security' && $name == 'password') {
                    $co['type'] = 'password';
                }
                $this->cfg[$cat][$name] = $co;
            }
            if (empty($this->cfg[$cat])) {
                unset($this->cfg[$cat]);
            }
        }
    }
}


/**
 * Editing of core language files.
 *
 * @category CMSimple_XH
 * @package  XH
 * @author   The CMSimple_XH developers <devs@cmsimple-xh.org>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://cmsimple-xh.org/
 */
class XH_CoreLangFileEdit extends XH_CoreArrayFileEdit
{
    /**
     * Constructs an instance.
     *
     * @global array The localization of the core.
     */
    function XH_CoreLangFileEdit()
    {
        global $tx;

        parent::XH_CoreArrayFileEdit();
        $this->varName = 'tx';
        $this->params = array(
            'form' => 'array',
            'file' => 'language',
            'action' => 'save'
        );
        $this->redir = '?file=language&action=array';
        $this->cfg = array();
        // TODO: sort?
        foreach ($tx as $cat => $opts) {
            $this->cfg[$cat] = array();
            foreach ($opts as $name => $val) {
                // don't show or save the following
                if ($cat == 'meta' && $name =='codepage') {
                    continue;
                }
                $co = array('val' => $val, 'type' => 'text');
                if ($cat == 'subsite' && $name == 'template') {
                    $co['type'] = 'enum';
                    $co['vals'] = $this->selectOptions(
                        'templates', '/^([^\.]*)$/i'
                    );
                    array_unshift($co['vals'], '');
                }
                $this->cfg[$cat][$name] = $co;
            }
        }
    }
}


/**
 * The abstract base class for plugin config file editing.
 *
 * @category CMSimple_XH
 * @package  XH
 * @author   The CMSimple_XH developers <devs@cmsimple-xh.org>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://cmsimple-xh.org/
 *
 * @abstract
 */
class XH_PluginArrayFileEdit extends XH_ArrayFileEdit
{
    /**
     * The name of the config array variable.
     *
     * @access protected
     * @var    string
     */
    var $varName = null;

    /**
     * Constructs an instance.
     *
     * @global array  The paths of system files and folders.
     * @global string The name of the currently loading plugin.
     */
    function XH_PluginArrayFileEdit()
    {
        global $pth, $plugin;

        $this->plugin = $plugin;
        $this->caption = $plugin;
    }

    /**
     * Returns the the file contents as string for saving.
     *
     * @access protected
     * @return string
     */
    function asString()
    {
        $o = "<?php\n\n";
        foreach ($this->cfg as $cat => $opts) {
            foreach ($opts as $name => $opt) {
                $key = $cat;
                !empty($name) and $key .= "_$name";
                $opt = addcslashes($opt['val'], "\0..\37\"\$\\");
                $o .= "\$$this->varName['$this->plugin']['$key']=\"$opt\";\n";
            }
        }
        $o .= "\n?>\n";
        return $o;
    }
}


/**
 * Editing of plugin config files.
 *
 * @category CMSimple_XH
 * @package  XH
 * @author   The CMSimple_XH developers <devs@cmsimple-xh.org>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://cmsimple-xh.org/
 */
class XH_PluginConfigFileEdit extends XH_PluginArrayFileEdit
{
    /**
     * Constructs an instance.
     *
     * @global array
     * @global string
     * @global array
     * @global array
     */
    function XH_PluginConfigFileEdit()
    {
        global $pth, $plugin, $plugin_cf, $plugin_tx;

        parent::XH_PluginArrayFileEdit();
        $fn = $pth['folder']['plugins'] . $plugin . '/config/metaconfig.php';
        if (is_readable($fn)) {
            include $fn;
        }
        $mcf = isset($plugin_mcf[$plugin]) ? $plugin_mcf[$plugin] : array();
        $this->filename = $pth['file']['plugin_config'];
        $this->params = array('admin' => 'plugin_config',
                              'action' => 'plugin_save');
        $this->redir = "?&$plugin&admin=plugin_config&action=plugin_edit";
        $this->varName = 'plugin_cf';
        $this->cfg = array();
        foreach ($plugin_cf[$plugin] as $key => $val) {
            list($cat, $name) = $this->splitKey($key);
            $type = isset($mcf[$key]) ? $mcf[$key] : 'string';
            if (strpos($type, 'enum:') === 0) {
                $vals = explode(',', substr($type, strlen('enum:')));
                $type = 'enum';
            } else {
                $vals = null;
            }
            $co = array('val' => $val, 'type' => $type,  'vals' => $vals);
            if (isset($plugin_tx[$plugin]["cf_$key"])) {
                $co['hint'] = $plugin_tx[$plugin]["cf_$key"];
            }
            $this->cfg[$cat][$name] = $co;
        }
    }
}


/**
 * Editing of plugin language files.
 *
 * @category CMSimple_XH
 * @package  XH
 * @author   The CMSimple_XH developers <devs@cmsimple-xh.org>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://cmsimple-xh.org/
 */
class XH_PluginLanguageFileEdit extends XH_PluginArrayFileEdit
{
    /**
     * Constructs an instance.
     * 
     * @global array
     * @global string
     * @global array
     */
    function XH_PluginLanguageFileEdit()
    {
        global $pth, $plugin, $plugin_tx;

        parent::XH_PluginArrayFileEdit();
        $this->filename = $pth['file']['plugin_language'];
        $this->params = array('admin' => 'plugin_language',
                              'action' => 'plugin_save');
        $this->redir = "?&$plugin&admin=plugin_language&action=plugin_edit";
        $this->varName = 'plugin_tx';
        $this->cfg = array();
        foreach ($plugin_tx[$plugin] as $key => $val) {
            list($cat, $name) = $this->splitKey($key);
            $co = array('val' => $val, 'type' => 'text');
            $this->cfg[$cat][$name] = $co;
        }
    }
}

?>