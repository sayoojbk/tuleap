<?php
/**
 * Copyright © Enalean, 2011 - 2018. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2006
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

class Docman_View_ItemDetailsSectionHistory extends Docman_View_ItemDetailsSection {

    public $logger;

    public $display_access_logs;

    public function __construct($item, $url, $display_access_logs, $logger) {
        parent::__construct($item, $url, 'history', $GLOBALS['Language']->getText('plugin_docman','details_history'));
        $this->logger = $logger;
        $this->display_access_logs = $display_access_logs;
    }

    public function getContent($params = []) {
        $content = '';

        if ($this->item instanceof Docman_File) {
            $content .= $this->getFileVersions();
        } elseif ($this->item instanceof Docman_Link) {
            $content .= $this->getLinkVersions();
        }

        if ($this->logger) {
            $content .= $this->logger->fetchLogsForItem($this->item->getId(), $this->display_access_logs);
        }

        return $content;
    }

    private function getFileVersions() {
        $uh      = UserHelper::instance();
        $content = '<h3>'. $GLOBALS['Language']->getText('plugin_docman','details_history_versions') .'</h3>';
        $version_factory = new Docman_VersionFactory();
        $approvalFactory = Docman_ApprovalTableFactoriesFactory::getFromItem($this->item);
        $versions        = $version_factory->getAllVersionForItem($this->item);

        if ($versions) {
            if (count($versions)) {
                $titles = array();
                $titles[] = $GLOBALS['Language']->getText('plugin_docman','details_history_versions_version');
                $titles[] = $GLOBALS['Language']->getText('plugin_docman','details_history_versions_date');
                $titles[] = $GLOBALS['Language']->getText('plugin_docman','details_history_versions_author');
                $titles[] = $GLOBALS['Language']->getText('plugin_docman','details_history_versions_label');
                $titles[] = $GLOBALS['Language']->getText('plugin_docman','details_history_versions_changelog');
                $titles[] = $GLOBALS['Language']->getText('plugin_docman','details_history_versions_approval');
                $titles[] = $GLOBALS['Language']->getText('plugin_docman','details_history_versions_delete_version');
                $content .= html_build_list_table_top($titles, false, false, false);
                $odd_even = array('boxitem', 'boxitemalt');
                $i = 0;
                foreach ($versions as $key => $nop) {
                    $download = Docman_View_View::buildUrl($this->url, array(
                        'action' => 'show',
                        'id'     => $this->item->getId(),
                        'version_number' => $versions[$key]->getNumber()
                    ));
                    $delete = Docman_View_View::buildUrl($this->url, array (
                        'action' =>'confirmDelete',
                        'id'     => $this->item->getId(),
                        'version' => $versions[$key]->getNumber()
                    ));
                    $user = $versions[$key]->getAuthorId() ? $uh->getDisplayNameFromUserId($versions[$key]->getAuthorId()) : $GLOBALS['Language']->getText('plugin_docman','details_history_anonymous');
                    $content .= '<tr class="'. $odd_even[$i++ % count($odd_even)] .'">';
                    $content .= '<td align="center"><a href="'. $download .'">'. $versions[$key]->getNumber() .'</a></td>';
                    $content .= '<td>'. html_time_ago($versions[$key]->getDate()) .'</td>';
                    $content .= '<td>'. $this->hp->purify($user)                                                  .'</td>';
                    $content .= '<td>'. $this->hp->purify($versions[$key]->getLabel())         .'</td>';
                    $content .= '<td>'. $this->hp->purify($versions[$key]->getChangelog(), CODENDI_PURIFIER_LIGHT) .'</td>';

                    $table = $approvalFactory->getTableFromVersion($versions[$key]);
                    if($table != null) {
                        $appTable = Docman_View_View::buildUrl($this->url, array(
                            'action' => 'details',
                            'section' => 'approval',
                            'id' => $this->item->getId(),
                            'version' => $versions[$key]->getNumber(),
                        ));
                        $content .= '<td align="center"><a href="'.$appTable.'">'.$titles[] = $GLOBALS['Language']->getText('plugin_docman','details_history_versions_approval_show').'</a></td>';
                    } else {
                        $content .= '<td></td>';
                    }
                    $content .= '<td align="center"><a href="'.$delete.'"><img src="'.util_get_image_theme("ic/trash.png").'" height="16" width="16" border="0"></a></td>';
                    $content .= '</tr>';
                }
                $content .= '</table>';
            } else {
                $content .= '<div>'. $GLOBALS['Language']->getText('plugin_docman','details_history_versions_no') .'</div>';
            }
        } else {
            $content .= '<div>'. $GLOBALS['Language']->getText('plugin_docman','details_history_versions_error') .'</div>';
        }

        return $content;
    }

    private function getLinkVersions() {
        $uh      = UserHelper::instance();
        $content = '<h3>'. $GLOBALS['Language']->getText('plugin_docman','details_history_versions') .'</h3>';

        $version_factory = new Docman_LinkVersionFactory();
        $versions        = $version_factory->getAllVersionForItem($this->item);

        if ($versions) {
            $titles = array(
                $GLOBALS['Language']->getText('plugin_docman','details_history_versions_version'),
                $GLOBALS['Language']->getText('plugin_docman','details_history_versions_date'),
                $GLOBALS['Language']->getText('plugin_docman','details_history_versions_author'),
                $GLOBALS['Language']->getText('plugin_docman','details_history_versions_label'),
                $GLOBALS['Language']->getText('plugin_docman','details_history_versions_changelog'),
            );
            $content .= html_build_list_table_top($titles, false, false, false);

            $odd_even = array('boxitem', 'boxitemalt');
            $i = 0;

            foreach (array_keys($versions) as $key) {
                $download = Docman_View_View::buildUrl($this->url, array(
                    'action'         => 'show',
                    'id'             => $this->item->getId(),
                    'version_number' => $versions[$key]->getNumber()
                ));
                $user = $versions[$key]->getAuthorId() ? $uh->getDisplayNameFromUserId($versions[$key]->getAuthorId()) : $GLOBALS['Language']->getText('plugin_docman','details_history_anonymous');
                $content .= '<tr class="'. $odd_even[$i++ % count($odd_even)] .'">';
                $content .= '<td align="center"><a href="'. $download .'">'. $versions[$key]->getNumber().'</a></td>';
                $content .= '<td>'. html_time_ago($versions[$key]->getDate()) .'</td>';
                $content .= '<td>'. $this->hp->purify($user).'</td>';
                $content .= '<td>'. $this->hp->purify($versions[$key]->getLabel()).'</td>';
                $content .= '<td>'. $this->hp->purify($versions[$key]->getChangelog(), CODENDI_PURIFIER_LIGHT) .'</td>';
                $content .= '</tr>';
            }
            $content .= '</table>';
        } else {
            $content .= '<div>'. $GLOBALS['Language']->getText('plugin_docman','details_history_versions_error') .'</div>';
        }

        return $content;
    }
}
