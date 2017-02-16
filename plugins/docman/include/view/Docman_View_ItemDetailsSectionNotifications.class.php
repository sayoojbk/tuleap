<?php
/*
 * Copyright (c) Enalean, 2013-2017. All rights reserved
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2006
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 *
 */
require_once('Docman_View_ItemDetailsSection.class.php');

class Docman_View_ItemDetailsSectionNotifications extends Docman_View_ItemDetailsSection {
    var $notificationsManager;
    var $token;
    function Docman_View_ItemDetailsSectionNotifications(&$item, $url, &$notificationsManager, $token) {
        parent::Docman_View_ItemDetailsSection($item, $url, 'notifications', $GLOBALS['Language']->getText('plugin_docman', 'details_notifications'));
        $this->notificationsManager =& $notificationsManager;
        $this->token = $token;
    }
    function getContent() {
        $content = '<dl><fieldset><legend>'. $GLOBALS['Language']->getText('plugin_docman', 'details_notifications') .'</legend>';
        $content .= '<dd>';
        $content .= '<form action="" method="POST">';
        $content .= '<p>';
        if ($this->token) {
            $content .= '<input type="hidden" name="token" value="'. $this->token .'" />';
        }
        $content .= '<input type="hidden" name="action" value="monitor" />';
        $content .= '<input type="hidden" name="id" value="'. $this->item->getId() .'" />';
        $um   =& UserManager::instance();
        $user =& $um->getCurrentUser();
        $checked  = !$user->isAnonymous() && $this->notificationsManager->exist($user->getId(), $this->item->getId()) ? 'checked="checked"' : '';
        $disabled = $user->isAnonymous() ? 'disabled="disabled"' : '';
        $content .= '<input type="hidden" name="monitor" value="0" />';
        $content .= '<label class="checkbox" for="plugin_docman_monitor_item">';
        $content .= '<input type="checkbox" name="monitor" value="1" id="plugin_docman_monitor_item" '. $checked .' '. $disabled .' />'. $GLOBALS['Language']->getText('plugin_docman', 'details_notifications_sendemail');
        $content .= '</label></p>';
        $content .= $this->item->accept($this, array('user' => &$user));
        $content .= '<p><input type="submit" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" /></p>';
        $content .= '</form>';
        $content .= '</dd></fieldset></dl>';
        $content .= '<dl>'.$this->displayListeningUsers($this->item->getId()).'</dl>';
        return $content;
    }

    /**
     * Show list of people monitoring the document directly or indirectly by monitoring one of the parents and its subitems
     *
     * @param Integer $itemId Id of the document
     *
     * @return String
     */
    private function displayListeningUsers($itemId)
    {
        $dpm        = Docman_PermissionsManager::instance($this->item->getGroupId());
        $userHelper = new UserHelper();
        $um         = UserManager::instance();
        $purifier   = Codendi_HTMLPurifier::instance();
        $content    = '';
        if ($dpm->userCanManage($um->getCurrentUser(), $itemId)) {
            $listeners = $this->notificationsManager->getListeningUsers($this->item);
            if (!empty($listeners)) {
                $content .= '<fieldset><legend>'. $purifier->purify($GLOBALS['Language']->getText('plugin_docman', 'details_listeners')) .'</legend>';
                $content .= '<div class="docman_help plugin-docman-notifications-list-help">'.$GLOBALS['Language']->getText('plugin_docman', 'details_notifications_help').'</div>';
                $content .= '<form name="remove_monitoring" method="POST" action="">';
                $content .= '<input type="hidden" name="action" value="remove_monitoring" />';
                $content .= '<table class="table table-bordered plugin-docman-notifications-list">';
                $content .= '<thead><tr>';
                $content .= '<th><i class="icon-trash"></i></th>';
                $content .= '<th class="plugin-docman-notifications-list-user">'. $purifier->purify(dgettext('tuleap-docman', 'Notified people')) .'</th>';
                $content .= '<th class="plugin-docman-notifications-list-document">'. $purifier->purify($GLOBALS['Language']->getText('plugin_docman', 'details_notifications_monitored_doc')) .'</th>';
                $content .= '</tr></thead>';
                foreach ($listeners as $userId => $item) {
                    $content .= '<tr>';
                    $user = $um->getUserById($userId);
                    $content .= '<td>';
                    if ($this->item == $item) {
                        $content .= '<input type="checkbox" value="'. $purifier->purify($userId) .'" name="listeners_to_delete[]">';
                    } else {
                        $content .= '<input type="checkbox" value="'. $purifier->purify($userId) .'" name="listeners_to_delete[]" disabled="disabled">';
                    }
                    $content .= '</td>';
                    $content .= '<td>'. $purifier->purify($userHelper->getDisplayName($user->getName(), $user->getRealName())) .'</td>';
                    $content .= '<td>'. $purifier->purify($item->getTitle()) .'</td>';
                    $content .= '</tr>';
                }
                $content .= '</tbody></table>';
                $content .= '<input type="submit" value="'. $purifier->purify($GLOBALS['Language']->getText('plugin_docman', 'action_delete')) .'">';
                $content .= '</form>';
            }
            $content .= $this->addListeningUser($itemId);
            $content .= '</fieldset>';
        }
        return $content;
    }

    /**
     * Add a user to the list of peoples that are monitoring a given item.
     *
     * @param Integer $itemId Id of the document
     *
     * @return String
     */
    function addListeningUser($itemId) {
        $content = '<tr><td colspan="2"><hr width="100%" size="1" NoShade></td></tr>';
        $content .= '<tr><form name="add_monitoring" method="POST" action="">';
        $content .= '<input type="hidden" name="action" value="add_monitoring">';
        $content .= '<input type="hidden" name="item_id" value="'. $itemId .'">';
        $content .= '<table>';
        $content .= '<tr><td><b>'. $GLOBALS['Language']->getText('plugin_docman', 'notifications_add_user_title') .'</b></td></tr>';
        $content .= '<tr><td><textarea name="listeners_to_add" value="" id="listeners_to_add" rows="2" cols="50"></textarea></td></tr>';

        //checkbox to enable cascade monitoring
        $content .= '<tr><td>';
        $content .= '<label class="checkbox" for="plugin_docman_monitor_add_user_cascade">';
        $content .= '<input type="checkbox" name="monitor_cascade" value="1" id="plugin_docman_monitor_add_user_cascade" />'. $GLOBALS['Language']->getText('plugin_docman', 'notifications_add_user_cascade');
        $content .= '</label></td></tr></table>';

        //autocompletion on "add_user" field.
        $autocomplete = "new UserAutoCompleter('listeners_to_add','".
                        util_get_dir_image_theme()."',true);";
        $GLOBALS['Response']->includeFooterJavascriptSnippet($autocomplete);
        $content .= '<input type="submit" name="submit" value="'.
                    $GLOBALS['Language']->getText('plugin_docman', 'notifications_add_user') .'"></td></form></tr>';
        return $content;
    }

    function visitEmpty(&$item, $params) {
        return $this->visitDocument($item, $params);
    }
    function visitWiki(&$item, $params) {
        return $this->visitDocument($item, $params);
    }
    function visitLink(&$item, $params) {
        return $this->visitDocument($item, $params);
    }
    function visitEmbeddedFile(&$item, $params) {
        return $this->visitDocument($item, $params);
    }
    function visitFile(&$item, $params) {
        return $this->visitDocument($item, $params);
    }
    function visitDocument(&$item, $params) {
        return '';
    }
    function visitFolder(&$item, $params) {
        $content = '<blockquote>';
        $checked  = !$params['user']->isAnonymous() && $this->notificationsManager->exist($params['user']->getId(), $this->item->getId(), PLUGIN_DOCMAN_NOTIFICATION_CASCADE) ? 'checked="checked"' : '';
        $disabled = $params['user']->isAnonymous() ? 'disabled="disabled"' : '';
        $content .= '<input type="hidden" name="cascade" value="0" />';
        $content .= '<input type="checkbox" name="cascade" value="1" id="plugin_docman_monitor_cascade_item" '. $checked .' '. $disabled .' />';
        $content .= '<label for="plugin_docman_monitor_cascade_item">'. $GLOBALS['Language']->getText('plugin_docman', 'details_notifications_cascade_sendemail') .'</label>';
        $content .= '</blockquote>';
        return $content;
    }
}

?>
