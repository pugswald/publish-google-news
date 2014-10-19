<?php
/*
Plugin Name: Publish Google News
Description: Displays a selectable Google News RSS feed, inline, widget or in
             theme.  Allows publishing of news items using shortened URLs.
Version:     0.1
Author:      Harry Sorensen
License:     GPL

Based on Google news plugin code by Olav Kolbu http://www.kolbu.com/2008/04/07/google-news-plugin/
Minor parts of WordPress-specific code from various other GPL plugins.

*/
/*
Copyright (C) 2014 Harry Sorensen

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

include_once(ABSPATH . WPINC . '/rss.php');

global $publish_google_news_instance;

if ( ! class_exists('publish_google_news_plugin')) {
    class publish_google_news_plugin {

        // So we don't have to query database on every replacement
        var $settings;

        var $regions = array(
            'Australia' => 'au',
            'India' => 'in',
            'Israel' => 'en_il',
            'Malaysia' => 'en_my',
            'New Zealand' => 'nz',
            'Pakistan' => 'en_pk',
            'Philippines' => 'en_ph',
            'Singapore' => 'en_sg',
            '&#1575;&#1604;&#1593;&#1575;&#1604;&#1605; &#1575;&#1604;&#1593;&#1585;&#1576;&#1610; (Arabic)' => 'ar_me',
            '&#20013;&#22269;&#29256; (China)' => 'cn',
            '&#39321;&#28207;&#29256; (Hong Kong)' => 'hk',
            '&#2349;&#2366;&#2352;&#2340; (India)' => 'hi_in',
            '&#2980;&#2990;&#3007;&#2996;&#3021; (India)' => 'ta_in',
            '&#3374;&#3378;&#3375;&#3390;&#3379;&#3330; (India)' => 'ml_in',
            '&#3108;&#3142;&#3122;&#3137;&#3095;&#3137; (India)' => 'te_in',
            '&#1497;&#1513;&#1512;&#1488;&#1500; (Israel)' => 'iw_il',
            '&#26085;&#26412; (Japan)' => 'jp',
            '&#54620;&#44397; (Korea)' => 'kr',
            '&#21488;&#28771;&#29256; (Taiwan)' => 'tw',
	    'Việt Nam (Vietnam)' => 'vi_vn',
            '-------------' => 'us',
            'België' => 'nl_be',
            'Belgique' => 'fr_be',
            'Botswana' => 'en_bw',
            'Česká republika' => 'cs_cz',
            'Deutschland' => 'de',
            'España' => 'es',
            'Ethiopia' => 'en_et',
            'France' => 'fr',
            'Ghana' => 'en_gh',
            'Ireland' => 'en_ie',
            'Italia' => 'it',
            'Kenya' => 'en_ke',
            'Magyarország' => 'hu_hu',
            'Namibia' => 'en_na',
            'Nederland' => 'nl_nl',
            'Nigeria' => 'en_ng',
            'Norge' => 'no_no',
            'Österreich' => 'de_at',
            'Polska' => 'pl_pl',
            'Portugal' => 'pt:PT_pt',
            'Schweiz' => 'de_ch',
            'South Africa' => 'en_za',
            'Suisse' => 'fr_ch',
            'Sverige' => 'sv_se',
            'Tanzania' => 'en_tz',
            'Türkiye' => 'tr_tr',
            'Uganda' => 'en_ug',
            'U.K.' => 'uk',
            'Zimbabwe' => 'en_zw',
            '&#917;&#955;&#955;&#940;&#948;&#945; (Greece)' => 'el_gr',
            '&#1056;&#1086;&#1089;&#1089;&#1080;&#1103; (Russia)' => 'ru_ru',
	    '&#1059;&#1082;&#1088;&#1072;&#1080;&#1085;&#1072; (Ukraine)' => 'ru_ua',
	    '&#1059;&#1082;&#1088;&#1072;&#1111;&#1085;&#1072; (Ukraine)' => 'uk_ua',
            '------------' => 'us',
            'Argentina' => 'es_ar',
            'Brasil' => 'pt:BR_br',
            'Canada English' => 'ca',
            'Canada Français' => 'fr_ca',
            'Chile' => 'es_cl',
            'Colombia' => 'es_co',
            'Cuba' => 'es_cu',
            'Estados Unidos' => 'es_us',
            'México' => 'es_mx',
            'Perú' => 'es_pe',
            'U.S.' => 'us',
            'Venezuela' => 'es_ve',
        );

        var $newstypes = array(
            'All' => '',
            'Top News' => 'h',
            'Foreign' => 'w',
            'Domestic' => 'n',
            'Business' => 'b',
            'Sci/Tech' => 't',
            'Health' => 'm',
            'Sports' => 's',
            'Entertainment' => 'e',
        );

        var $outputtypes = array(
            'Standard' => '',
            'Text Only' => 't',
            'With Images' => '&imv=1',
        );

        var $desctypes = array(
            'Short' => '',
            'Long' => 'l',
        );

        // Constructor
        function publish_google_news_plugin() {

            // Form POSTs dealt with elsewhere
            if ( is_array($_POST) ) {
                if ( $_POST['publish_google_news-widget-submit'] ) {
                    $tmp = $_POST['publish_google_news-widget-feed'];
                    $alloptions = get_option('publish_google_news');
                    if ( $alloptions['widget-1'] != $tmp ) {
                        if ( $tmp == '*DEFAULT*' ) {
                            $alloptions['widget-1'] = '';
                        } else {
                            $alloptions['widget-1'] = $tmp;
                        }
                        update_option('publish_google_news', $alloptions);
                    }
                } else if ( $_POST['publish_google_news-options-submit'] ) {
                    // noop
                } else if ( $_POST['publish_google_news-submit'] ) {
                    // noop
                }
            }

            add_filter('the_content', array(&$this, 'insert_news')); 
            add_action('admin_menu', array(&$this, 'admin_menu'));
            add_action('plugins_loaded', array(&$this, 'widget_init'));

            // Hook for theme coders/hackers
            add_action('publish_google_news', array(&$this, 'display_feed'));

 
            // Makes it backwards compat pre-2.5 I hope
            if ( function_exists('add_shortcode') ) {
                add_shortcode('publish-google-news', array(&$this, 'my_shortcode_handler'));
             }

        }

        // *************** Admin interface ******************

        // Callback for admin menu
        function admin_menu() {
            add_options_page('Publish Google News Options', 'Publish Google News',
                             'administrator', __FILE__, 
                              array(&$this, 'plugin_options'));
            add_management_page('Publish Google News', 'Publish Google News', 
                                'administrator', __FILE__,
                                array(&$this, 'admin_manage'));
               
        }

        // Settings -> Publish Google News
        function plugin_options() {

           if (get_bloginfo('version') >= '2.7') {
               $manage_page = 'tools.php';
            } else {
               $manage_page = 'edit.php';
            }
            print <<<EOT
            <div class="wrap">
            <h2>Publish Google News</h2>
            <p>This plugin allows you to define a number of Publish Google News 
               feeds and have them displayed anywhere in content, in a widget
               or in a theme. </p>
            <p>Once the feed is populated, the user may publish a story using
               the original title and body snippet along with shortened URLs.
            </p>
            <p>Any number of inline replacements or theme
               inserts can be made, but only one widget instance is
               permitted in this release. To use the feeds insert one or more
               of the following special html comments or Shortcodes 
               anywhere in user content. Note that Shortcodes, i.e. the
               ones using square brackets, are only available in 
               WordPress 2.5 and above.<
            </p>
            <ul><li><b>&lt;--publish-google-news--&gt</b> (for default feed)</li>
                <li><b>&lt;--publish-google-news#feedname--&gt</b></li>
                <li><b>[publish-google-news]</b> (also for default feed)</li>
                <li><b>[publish-google-news name="feedname"]</b></li>
            </ul>
            <p>To insert in a theme call <b>do_action('publish_google_news');</b> or 
               alternatively <b>do_action('publish_google_news', 'feedname');</b>
            </p>
            <p>To manage feeds, go to <a href="$manage_page?page=publish-google-news/publish_google_news.php">Manage -> Publish Google News</a>, where you will also find more information.
            </p>
            <p><a href="http://www.kolbu.com/2008/04/07/publish-google-news-plugin/">Google News Widget Home Page</a>
               <a href="http://www.google.com/support/news/bin/answer.py?hl=en&answer=59255">Google Terms Of Use</a>
            </p>
            </div>

EOT;
        }

        // Manage -> Publish Google News
        function admin_manage() {
            // Edit/delete links
            $mode = trim($_GET['mode']);
            $id = trim($_GET['id']);

            $this->upgrade_options();

            $alloptions = get_option('publish_google_news');

            $flipregions     = array_flip($this->regions);
            $flipnewstypes   = array_flip($this->newstypes);
            $flipoutputtypes = array_flip($this->outputtypes);
            $flipdesctypes   = array_flip($this->desctypes);

            if ( is_array($_POST) && $_POST['publish_google_news-submit'] ) {

                $newoptions = array();
                $id                       = $_POST['publish_google_news-id'];

                $newoptions['name']       = $_POST['publish_google_news-name'];
                $newoptions['title']      = $_POST['publish_google_news-title'];
                $newoptions['region']     = $_POST['publish_google_news-region'];
                $newoptions['newstype']   = $_POST['publish_google_news-newstype'];
                $newoptions['outputtype'] = $_POST['publish_google_news-outputtype'];
                $newoptions['desctype']   = $_POST['publish_google_news-desctype'];
                $newoptions['numnews']    = $_POST['publish_google_news-numnews'];
                $newoptions['query']      = $_POST['publish_google_news-query'];
                $newoptions['feedtype']   = $flipregions[$newoptions['region']].' : '.
                                            $flipnewstypes[$newoptions['newstype']];

                if ( $alloptions['feeds'][$id] == $newoptions ) {
                    $text = 'No change...';
                    $mode = 'main';
                } else {
                    $alloptions['feeds'][$id] = $newoptions;
                    update_option('publish_google_news', $alloptions);
 
                    $mode = 'save';
                }
            } else if ( is_array($_POST) && $_POST['publish_google_news-options-cachetime-submit'] ) {
                if ( $_POST['publish_google_news-options-cachetime'] != $alloptions['cachetime'] ) {
                    $alloptions['cachetime'] = $_POST['publish_google_news-options-cachetime'];
                    update_option('publish_google_news', $alloptions);
                    $text = "Cache time changed to {$alloptions[cachetime]} seconds.";
                } else {
                    $text = "No change in cache time...";
                }
                $mode = 'main';
            }

            if ( $mode == 'newfeed' ) {
                $newfeed = 0;
                foreach ($alloptions['feeds'] as $k => $v) {
                    if ( $k > $newfeed ) {
                        $newfeed = $k;
                    }
                }
                $newfeed += 1;

                $text = "Please configure new feed and press Save.";
                $mode = 'main';
            }

            if ( $mode == 'save' ) {
                $text = "Saved feed {$alloptions[feeds][$id][name]} [$id].";
                $mode = 'main';
            }

            if ( $mode == 'edit' ) {
                if ( ! empty($text) ) {
                     echo '<!-- Last Action --><div id="message" class="updated fade"><p>'.$text.'</p></div>';
                }
                $text = "Editing feed {$alloptions[feeds][$id][name]} [$id].";

                $edit_id = $id;
                $mode = 'main';
            }

            if ( $mode == 'delete' ) {

                $text = "Deleted feed {$alloptions[feeds][$id][name]} [$id].";
                
                unset($alloptions['feeds'][$id]);

                update_option('publish_google_news', $alloptions);
 
                $mode = 'main';
            }

            // main
            if ( empty($mode) or ($mode == 'main') ) {

                if ( ! empty($text) ) {
                     echo '<!-- Last Action --><div id="message" class="updated fade"><p>'.$text.'</p></div>';
                }
                print '<div class="wrap">';
                print ' <h2>';
                print _e('Manage Publish Google News Feeds','publish_google_news');
                print '</h2>';
                print ' <table id="the-list-x" width="100%" cellspacing="3" cellpadding="3">';
                print '  <thead>';
                print '   <tr>';
                print '    <th scope="col">';
                print _e('Key','publish_google_news');
                print '</th>';
                print '    <th scope="col">';
                print _e('Name','publish_google_news');
                print '</th>';
                print '    <th scope="col">';
                print _e('Admin-defined title','publish_google_news');
                print '</th>';
                print '    <th scope="col">';
                print _e('Region','publish_google_news');
                print '</th>';
                print '    <th scope="col">';
                print _e('Type','publish_google_news');
                print '</th>';
                print '    <th scope="col">';
                print _e('Output','publish_google_news');
                print '</th>';
                print '    <th scope="col">';
                print _e('Item length','publish_google_news');
                print '</th>';
                print '    <th scope="col">';
                print _e('Max items','publish_google_news');
                print '</th>';
                print '    <th scope="col">';
                print _e('Optional query filter','publish_google_news');
                print '</th>';
                print '    <th scope="col" colspan="3">';
                print _e('Action','publish_google_news');
                print '</th>';
                print '   </tr>';
                print '  </thead>';

                if (get_bloginfo('version') >= '2.7') {
                    $manage_page = 'tools.php';
                } else {
                    $manage_page = 'edit.php';
                }

                if ( $alloptions['feeds'] || $newfeed ) {
                    $i = 0;

                    foreach ($alloptions['feeds'] as $key => $val) {
                        if ( $i % 2 == 0 ) {
                            print '<tr class="alternate">';
                        } else {
                            print '<tr>';
                        }
                        if ( isset($edit_id) && $edit_id == $key ) {
                            print "<form name=\"publish_google_news_options\" action=\"".
                                  htmlspecialchars($_SERVER['REQUEST_URI']).
                                  "\" method=\"post\" id=\"publish_google_news_options\">";
                                    
                            print "<th scope=\"row\">".$key."</th>";
                            print '<td><input size="10" maxlength="20" id="publish_google_news-name" name="publish_google_news-name" type="text" value="'.$val['name'].'" /></td>';
                            print '<td><input size="20" maxlength="20" id="publish_google_news-title" name="publish_google_news-title" type="text" value="'.$val['title'].'" /></td>';
                            print '<td><select name="publish_google_news-region">';
                            $region = $val['region'];
                            foreach ($this->regions as $k => $v) {
                                print '<option '.(strcmp($v,$region)?'':'selected').' value="'.$v.'" >'.$k.'</option>';
                            }
                            print '</select></td>';
                            print '<td><select name="publish_google_news-newstype">';
                            $newstype = $val['newstype'];
                            foreach ($this->newstypes as $k => $v) {
                                print '<option '.(strcmp($v,$newstype)?'':'selected').' value="'.$v.'" >'.$k.'</option>';
                            }
                            print '</select></td>';
                            print '<td><select name="publish_google_news-outputtype">';
                            $outputtype = $val['outputtype'];
                            foreach ($this->outputtypes as $k => $v) {
                                print '<option '.(strcmp($v,$outputtype)?'':'selected').' value="'.$v.'" >'.$k.'</option>';
                            }
                            print '</select></td>';
                            print '<td><select name="publish_google_news-desctype">';
                            $desctype = $val['desctype'];
                            foreach ($this->desctypes as $k => $v) {
                                print '<option '.(strcmp($v,$desctype)?'':'selected').' value="'.$v.'" >'.$k.'</option>';
                            }
                            print '</select></td>';
                            print '<td><input size="3" maxlength="3" id="publish_google_news-numnews" name="publish_google_news-numnews" type="text" value="'.$val['numnews'].'" /></td>';
                            print '<td><input size="10" maxlength="50" id="publish_google_news-query" name="publish_google_news-query" type="text" value="'.$val['query'].'" /></td>';
                            print '<td><input type="submit" value="Save  &raquo;">';
                            print "</td>";
                            print "<input type=\"hidden\" id=\"publish_google_news-id\" name=\"publish_google_news-id\" value=\"$edit_id\" />";
                            print "<input type=\"hidden\" id=\"publish_google_news-submit\" name=\"publish_google_news-submit\" value=\"1\" />";
                            print "</form>";
                        } else {
                            print "<th scope=\"row\">".$key."</th>";
                            print "<td>".$val['name']."</td>";
                            print "<td>".$val['title']."</td>";
                            print "<td>".$flipregions[$val['region']]."</td>";
                            print "<td>".$flipnewstypes[$val['newstype']]."</td>";
                            print "<td>".$flipoutputtypes[$val['outputtype']]."</td>";
                            print "<td>".$flipdesctypes[$val['desctype']]."</td>";
                            print "<td>".$val['numnews']."</td>";
                            print "<td>".$val['query']."</td>";
                            print "<td><a href=\"$manage_page?page=publish-google-news/publish_google_news.php&amp;mode=edit&amp;id=$key\" class=\"edit\">";
                            print __('Edit','publish_google_news');
                            print "</a></td>\n";
                            print "<td><a href=\"$manage_page?page=publish-google-news/publish_google_news.php&amp;mode=delete&amp;id=$key\" class=\"delete\" onclick=\"javascript:check=confirm( '".__("This feed entry will be erased. Delete?",'publish_google_news')."');if(check==false) return false;\">";
                            print __('Delete', 'publish_google_news');
                            print "</a></td>\n";
                        }
                        print '</tr>';

                        $i++;
                    }
                    if ( $newfeed ) {

                        print "<form name=\"publish_google_news_options\" action=\"".
                              htmlspecialchars($_SERVER['REQUEST_URI']).
                              "\" method=\"post\" id=\"publish_google_news_options\">";
                                
                        print "<th scope=\"row\">".$newfeed."</th>";
                        print '<td><input size="10" maxlength="20" id="publish_google_news-name" name="publish_google_news-name" type="text" value="NEW" /></td>';
                        print '<td><input size="20" maxlength="20" id="publish_google_news-title" name="publish_google_news-title" type="text" value="" /></td>';
                        print '<td><select name="publish_google_news-region">';
                        $region = 'us';
                        foreach ($this->regions as $k => $v) {
                            print '<option '.(strcmp($v,$region)?'':'selected').' value="'.$v.'" >'.$k.'</option>';
                        }
                        print '</select></td>';
                        print '<td><select name="publish_google_news-newstype">';
                        foreach ($this->newstypes as $k => $v) {
                            print '<option value="'.$v.'" >'.$k.'</option>';
                        }
                        print '</select></td>';
                        print '<td><select name="publish_google_news-outputtype">';
                        foreach ($this->outputtypes as $k => $v) {
                            print '<option value="'.$v.'" >'.$k.'</option>';
                        }
                        print '</select></td>';
                        print '<td><select name="publish_google_news-desctype">';
                        foreach ($this->desctypes as $k => $v) {
                            print '<option value="'.$v.'" >'.$k.'</option>';
                        }
                        print '</select></td>';
                        print '<td><input size="3" maxlength="3" id="publish_google_news-numnews" name="publish_google_news-numnews" type="text" value="5" /></td>';
                        print '<td><input size="10" maxlength="50" id="publish_google_news-query" name="publish_google_news-query" type="text" value="" /></td>';
                        print '<td><input type="submit" value="Save  &raquo;">';
                        print "</td>";
                        print "<input type=\"hidden\" id=\"publish_google_news-id\" name=\"publish_google_news-id\" value=\"$newfeed\" />";
                        print "<input type=\"hidden\" id=\"publish_google_news-newfeed\" name=\"publish_google_news-newfeed\" value=\"1\" />";
                        print "<input type=\"hidden\" id=\"publish_google_news-submit\" name=\"publish_google_news-submit\" value=\"1\" />";
                        print "</form>";
                    } else {
                        print "</tr><tr><td colspan=\"12\"><a href=\"$manage_page?page=publish-google-news/publish_google_news.php&amp;mode=newfeed\" class=\"newfeed\">";
                        print __('Add extra feed','publish_google_news');
                        print "</a></td></tr>";

                    }
                } else {
                    print '<tr><td colspan="12" align="center"><b>';
                    print __('No feeds found(!)','publish_google_news');
                    print '</b></td></tr>';
                    print "</tr><tr><td colspan=\"12\"><a href=\"$manage_page?page=publish-google-news/publish_google_news.php&amp;mode=newfeed\" class=\"newfeed\">";
                    print __('Add feed','publish_google_news');
                    print "</a></td></tr>";
                }
                print ' </table>';
                print '<h2>';
                print _e('Global configuration parameters','publish_google_news');
                print '</h2>';
                print ' <form method="post">';
                print ' <table id="the-cachetime" cellspacing="3" cellpadding="3">';
                print '<tr><td><b>Cache time:</b></td>';
                print '<td><input size="6" maxlength="6" id="publish_google_news-options-cachetime" name="publish_google_news-options-cachetime" type="text" value="'.$alloptions['cachetime'].'" /> seconds</td>';
                print '<input type="hidden" id="publish_google_news-options-cachetime-submit" name="publish_google_news-options-cachetime-submit" value="1" />';
                print '<td><input type="submit" value="Save  &raquo;"></td></tr>';
                print ' </table>';
                print '</form>'; 

                print '<h2>';
                print _e('Information','publish_google_news');
                print '</h2>';
                print ' <table id="the-list-x" width="100%" cellspacing="3" cellpadding="3">';
                print '<tr><td><b>Key</b></td><td>Unique identifier used internally.</td></tr>';
                print '<tr><td><b>Name</b></td><td>Optional name to be able to reference a specific feed as e.g. ';
                print ' <b>&lt;!--publish_google_news#myname--&gt;</b>. ';
                print ' If more than one feed shares the same name, a random among these will be picked each time. ';
                print ' The one(s) without a name will be treated as the default feed(s), i.e. used for <b>&lt;!--publish_google_news--&gt;</b> ';
                print ' or widget feed type <b>*DEFAULT*</b>. If you have Wordpress 2.5 ';
                print ' or above, you can also use Shortcodes on the form <b>[publish-google-news]</b> ';
                print ' (for default feed) or <b>[publish-google-news name="feedname"]</b>. And finally ';
                print ' you can use <b>do_action(\'publish_google_news\');</b> or <b>do_action(\'publish_google_news\', \'feedname\');</b> ';
                print ' in themes.</td></tr>';
                print '<tr><td><b>Admin-defined title</b></td><td>Optional feed title. If not set, a reasonable title based on ';
                print 'Region and Type will be used. Note Google Terms of Service require you to show that the feeds come from ';
                print 'Publish Google News.</td></tr>';
                print '<tr><td><b>Region</b></td><td>The region/language of the feed.</td></tr>';
                print '<tr><td><b>Type</b></td><td>The type of news to present.</td></tr>';
                print '<tr><td><b>Output</b></td><td>Text only, allow for images or images with most news items. Note that ';
                print 'there will be text in all three cases.</td></tr>';
                print '<tr><td><b>Item length</b></td><td>Single sentence news items or 2-3 lines of text.</td></tr>';
                print '<tr><td><b>Max items</b></td><td>Maximum number of news items to show for this feed. If the feed contains ';
                print 'less than the requested items, only the number of items in the feed will obviously be displayed.</td></tr>';
                print '<tr><td><b>Optional query filter</b></td><td>Pass the requested news through a query filter for very ';
                print 'detailed control over the type of news to show. E.g. only sports news about the Yankees.</td></tr>';
                print '<tr><td colspan="12">In all cases, output will depend on original news source and can and will ';
                print 'differ from source to source. Google hasn\'t really done a great job with respect to formatting. ';
                print 'Note specifically that a query filter will change the output slightly, as this is how Google wants it.</td></tr>';
                print '<tr><td><b>Cache time</b></td><td>Minimum number of seconds that WordPress should cache a Publish Google News feed before fetching it again.</td></tr>';
                print ' </table>';
                print '</div>';
            }
        }

        // ************* Output *****************

        // The function that gets called from themes
        function display_feed($data) {
	    global $settings;
	    $settings = get_option('publish_google_news');
            print $this->random_feed($data);
            unset($settings);
        }

        // Callback for inline replacement
        function insert_news($data) {
            global $settings;

            // Allow for multi-feed sites
            $tag = '/<!--publish-google-news(|#.*?)-->/';

            // We may have old style options
            $this->upgrade_options();

            // Avoid getting this for each callback
            $settings   = get_option('publish_google_news');

            $result = preg_replace_callback($tag, 
                              array(&$this, 'inline_replace_callback'), $data);

            unset($settings);

            return $result;
        }


        // *********** Widget support **************
        function widget_init() {

            // Check for the required plugin functions. This will prevent fatal
            // errors occurring when you deactivate the dynamic-sidebar plugin.
            if ( !function_exists('register_sidebar_widget') )
                return;

            register_widget_control('Publish Google News', 
                                   array(&$this, 'widget_control'), 200, 100);

            // wp_* has more features, presumably fixed at a later date
            register_sidebar_widget('Publish Google News',
                                   array(&$this, 'widget_output'));

        }

        function widget_control() {

            // We may have old style options
            $this->upgrade_options();

            $alloptions = get_option('publish_google_news');
            $thisfeed = $alloptions['widget-1'];

            print '<p><label for="publish_google_news-feed">Select feed:</label>';
            print '<select style="vertical-align:middle;" name="publish_google_news-widget-feed">';

            $allfeeds = array();
            foreach ($alloptions['feeds'] as $k => $v) {
                $allfeeds[strlen($v['name'])?$v['name']:'*DEFAULT*'] = 1;
            } 
            foreach ($allfeeds as $k => $v) {
                print '<option '.($k==$thisfeed?'':'selected').' value="'.$k.'" >'.$k.'</option>';
            }
            print '</select><p>';
            print '<input type="hidden" id="publish_google_news-widget-submit" name="publish_google_news-widget-submit" value="1" />';


        }

        // Called every time we want to display ourselves as a sidebar widget
        function widget_output($args) {
            extract($args); // Gives us $before_ and $after_ I presume
                        
            // We may have old style options
            $this->upgrade_options();

            $alloptions = get_option('publish_google_news');
            $matching_feeds = array();
            foreach ($alloptions['feeds'] as $k => $v) {
                if ( (string)$v['name'] == $alloptions['widget-1'] ) { 
                    $matching_feeds[] = $k;
                } 
            }
            if ( ! count($matching_feeds) ) {
                if ( ! strlen($alloptions['widget-1']) ) {
                    $content = '<ul><b>No default feed available</b></ul>';
                } else {
                    $content = "<ul>Unknown feed name <b>{$alloptions[widget-1]}</b> used</ul>";
                }
                echo $before_widget;
                echo $before_title . __('Publish Google News<br>Error','publish_google_news') . $after_title . '<div>';
                echo $content;
                echo '</div>' . $after_widget;
                return;
            }
            $feed_id = $matching_feeds[rand(0, count($matching_feeds)-1)];
            $options = $alloptions['feeds'][$feed_id];

            $feedtype   = $options['feedtype'];
            $cachetime  = $alloptions['cachetime'];

            if ( strlen($options['title']) ) {
                $title = $options['title'];
            } else {
                $title = 'Publish Google News<br>'.$feedtype;
            }

            echo $before_widget;
            echo $before_title . $title . $after_title . '<div>';
            echo $this->get_feed($options, $cachetime);
            echo '</div>' . $after_widget;
        }

        // ************** The actual work ****************
        function get_feed(&$options, $cachetime) {
            // Handle posting before building list
            $this->post_news();

            if ( ! isset($options['region']) ) {
                return 'Options not set, visit plugin configuation screen.'; 
            }

            $region     = $options['region'] ? $options['region'] : 'us';
            $newstype   = $options['newstype'];
            $outputtype = $options['outputtype'];
            $query      = $options['query'];
            $numnews    = $options['numnews'] ? $options['numnews'] : 5;
            $desctype   = $options['desctype'];

            $result = '<div style="text-align:center;">Results for Query "'.$query.'"</div>'
                . '<table><tr><th>Post</th><th>Title</th><th>Date</th></tr><tr><th colspan=4>Body</th></tr>';
            $feedurl = 'http://news.google.com/news?output=rss';

            // This will also handle mixed mode text/image, when
            // we get the parsing under control...
            if ( $outputtype == 't' ) { 
                $region = 't'.$region;  // Consistent API, wassat?
            } else if ( strlen($outputtype) ) {
                $feedurl .= $outputtype;
            }
            $feedurl .= "&ned=$region"; 
            if ( strlen($newstype) ) {
                $feedurl .= "&topic=$newstype";
            }
            if ( strlen($query) ) {
                if ( substr($query,0,3) == 'OR ' ) {
                    $squery = urlencode(strtolower(substr($query,3)));
                    $feedurl .= "&as_oq=$squery";
                } else {
                    $squery = urlencode(strtolower($query));
                    $feedurl .= "&q=$squery";
                }
            }

            // Using the WP RSS fetcher (MagpieRSS). It has serious
            // GC problems though.
            define('MAGPIE_CACHE_AGE', $cachetime);
            define('MAGPIE_CACHE_ON', 1);
            define('MAGPIE_DEBUG', 1);

            $rss = fetch_rss($feedurl);

            if ( ! is_object($rss) ) {
                return 'Publish Google News unavailable</ul>';
            }
            $rss->items = array_slice($rss->items, 0, $numnews);
            foreach ( $rss->items as $item ) {
                //error_log("Item keys:".print_r(array_keys($item), TRUE));
                //error_log("Item title: ".print_r($item['title'], TRUE));
                //error_log("Item summary: ".print_r($item['summary'], TRUE));
                //error_log("Raw description:".print_r($description, TRUE));
                $description = html_entity_decode($item['description'], ENT_QUOTES | ENT_HTML401);
                //error_log(print_r(strip_tags($description,"<br>"), TRUE));
                $desc_arr = explode("<br />", strip_tags($description,"<br>"));
                //error_log("desc_arr=".print_r($desc_arr, TRUE));
                $pdate = $item['pubdate'];
                // Google's format is pretty inconsistent. 
                // Take longest line and call it the summary
                $summary = "";
                foreach ( $desc_arr as $line ){
                    if (strlen($line) > strlen($summary)) {
                        $summary = $line;
                    }
                }
                $post_title = $item['title'];
                $post_link = $item['link'];
                $query_args = array( 's' => $post_title );
                $query = new WP_Query( $query_args );
                if ( $query->found_posts == 0 ){
                //$posts = get_posts( array('name' => $post_title));
                //if (empty($posts)){
                    $form = '<form method="post" action="">
   <input type="submit" name="submit" value="Post">
   <input type="hidden" name="publish_google_news-title" value="'.$post_title.'">
   <input type="hidden" name="publish_google_news-summary" value="'.$summary.'">
   <input type="hidden" name="publish_google_news-link" value="'.$post_link.'">
</form>';
                } else {
                    $form = '<a href="'.get_permalink($posts[0]->ID).'">Done</a>';
                    //error_log('Post found:'.$posts[0]->ID);
                }
                $result .= "<tr><td>$form</td><td>$post_title</td><td>$pdate</td></tr>".
                           "<tr><td colspan=5>$summary</td></tr>";
             } 
            return $result.'</table>';
        }

        // *********** Shortcode support **************
        function my_shortcode_handler($atts, $content=null) {
            //error_log(print_r($atts, TRUE));
            global $settings;
            $settings = get_option('publish_google_news');
            return $this->random_feed($atts['name']);
            unset($settings);
        }

        
        // *********** inline replacement callback support **************
        function inline_replace_callback($matches) {

            if ( ! strlen($matches[1]) ) { // Default
                $feedname = '';
            } else {
                $feedname = substr($matches[1], 1); // Skip #
            }
            return $this->random_feed($feedname);
        }

        // ************** Support functions ****************

        function random_feed($name) {
            global $settings;

            $matching_feeds = array();
            foreach ($settings['feeds'] as $k => $v) {
                if ( (string)$v['name'] == $name ) { 
                    $matching_feeds[] = $k;
                } 
            }
            if ( ! count($matching_feeds) ) {
                if ( ! strlen($name) ) {
                    return '<ul><b>No default feed available</b></ul>';
                } else {
                    return "<ul>Unknown feed name <b>$name</b> used</ul>";
                }
            }
            $feed_id = $matching_feeds[rand(0, count($matching_feeds)-1)];
            $feed = $settings['feeds'][$feed_id];

            if ( strlen($feed['title']) ) {
                $title = $feed['title'];
            } else {
                $title = 'Publish Google News : '.$feed['feedtype'];
            }

            $result = '<!-- Start Publish Google News code -->';
            $result .= "<div id=\"publish-google-news-inline\"><h3>$title</h3>";
            $result .= $this->get_feed($feed, $settings['cachetime']);
            $result .= '</div><!-- End Publish Google News code -->';
            return $result;
        }


        // Unfortunately, we didn't finalize on a data structure
        // until version 2.1ish of the plugin so we need to upgrade
        // if needed
        function upgrade_options() {
            $options = get_option('publish_google_news');

            if ( !is_array($options) ) {

                // From 1.0
                $oldoptions = get_option('widget_publish_google_news_widget');
                if ( is_array($oldoptions) ) {
                    $flipregions     = array_flip($this->regions);
                    $flipnewstypes   = array_flip($this->newstypes);

                    $tmpfeed = array();
                    $tmpfeed['title']      = $oldoptions['title'];
                    $tmpfeed['name']       = '';
                    $tmpfeed['numnews']    = $oldoptions['numnews'];
                    $tmpfeed['region']     = $oldoptions['region'];
                    $tmpfeed['newstype']   = $oldoptions['newstype'];
                    $tmpfeed['outputtype'] = $oldoptions['outputtype'];
                    $tmpfeed['query']      = $oldoptions['query'];
                    $tmpfeed['feedtype']   = $flipregions[$tmpfeed['region']].
                                             ' : '.
                                             $flipnewstypes[$tmpfeed['newstype']];

                    $options = array();
                    $options['feeds']     = array( $tmpfeed );
                    $options['widget-1']  = 0;
                    $options['cachetime'] = 300;
                    
                    delete_option('widget_publish_google_news_widget');
                    update_option('publish_google_news', $options);
                } else {
                    // First time ever
                    $options = array();
                    $options['feeds']     = array( $this->default_feed() );
                    $options['widget-1']  = 0;
                    $options['cachetime'] = 300;
                    update_option('publish_google_news', $options);
                }
            } else {
                // From 2.0/2.0.1 to 2.1
                if ( array_key_exists('region', $options) ) {
                    $newoptions = array('feeds' => array( $options));
                    $newoptions['feeds'][0]['name'] = '';
                    $newoptions['widget-1']         = 0;
                    $newoptions['cachetime']        = 300;
                    update_option('publish_google_news', $newoptions);

                } else if ( 0 ) {
                    // Messed up options, start from scratch
                    $options = array();
                    $options['feeds']     = array( $this->default_feed() );
                    $options['widget-1']  = 0;
                    $options['cachetime'] = 300;
                    update_option('publish_google_news', $options);
                }
            }
        }

        function default_feed() {
            return array( 'numnews' => 5,
                          'region' => 'us',
                          'name' => '',
                          'feedtype' => 'U.S. : All');
        }
        
        function post_news() {
            if ( isset($_POST['publish_google_news-title'])){
                $url = $this->shorten_url($_POST['publish_google_news-link']);
                $name = $_POST['publish_google_news-title'].' - '.$url;
                $posts = get_posts( array('name' => $name));
                //error_log('Matching posts:'.print_r($posts,TRUE));
                if (empty($posts)){
                    $content = $_POST['publish_google_news-summary'];
                    $post = array(
                        'post_content' => $content,
                        'post_name' => $name,
                        'post_title' => $name,
                        'comment_status' => 'closed',
                        'post_status' => 'publish',
                    );
                    //error_log('Attempting to post '.$name);
                    $post_id = wp_insert_post($post);
                    //error_log('Created post '.$post_id);
                }
                
            }
        }
        
        function shorten_url($url){
            $content = '{"longUrl": "'.$url.'"}';
            $opts = array(
                'http'=>array(
                    'method' => 'POST',
                    'header' => "Content-Type: application/json\r\n"
                        . "Content-Length: ".strlen($content)."\r\n",
                    'content' => $content
                )
            );
            //error_log('Shorten options: '.print_r($opts,TRUE));
            $context = stream_context_create($opts);    
            $json = file_get_contents('https://www.googleapis.com/urlshortener/v1/url', 
                                      false, $context);
            $obj = json_decode($json, true);
            //error_log('Returned: '.print_r($obj,TRUE));
            $short_url = $obj['id'];
            return $short_url;
        }
    }

    // Instantiate
    $publish_google_news_instance &= new publish_google_news_plugin();

}
?>
