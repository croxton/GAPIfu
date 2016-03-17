<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * GAPIfu - Google Analytics PHP Interface for ExpressionEngine
 * 
 * @copyright Mark Croxton, Hallmark Design 2016
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 
 * @author Mark Croxton <mcroxton@hallmark-design.co.uk>
 * @version 1.0
 *
 */

require_once PATH_THIRD . 'gapifu/libraries/gapi.class.php';

$plugin_info = array(
  'pi_name' => 'GAPIfu',
  'pi_version' =>'1.0.0',
  'pi_author' =>'Mark Croxton',
  'pi_author_url' => 'http://www.hallmark-design.co.uk/',
  'pi_description' => 'Wrapper for Google Analytics PHP Interface (GAPI). Requires Stash.',
  'pi_usage' => Gapifu::usage()
  );

class Gapifu {

	/**
	 * The GAPI object
	 *
	 * @var        object
	 * @access     public
	 */
	private static $ga;

	/**
	 * Google Analytics API service email address
	 *
	 * @var        string
	 * @access     public
	 */
	private $email;

	/**
	 * Google Analytics API service key file path
	 *
	 * @var        string
	 * @access     public
	 */
	private $keyfile;

	/** 
	 * Constructor
	 *
	 * @access public
	 */
	public function __construct() 
	{
		if ( ! class_exists('Stash'))
		{
    		include_once PATH_THIRD . 'stash/mod.stash.php';
		}

		// get from config
		$this->email = ee()->config->item('gapifu_email');
		$this->keyfile = ee()->config->item('gapifu_keyfile');
	}
	
	/** 
	 * OAuth2
	 *
	 * @access private
	 */ 
	private function _auth()
	{
		if ( ! isset(self::$ga))
		{
			self::$ga = new gapi(
				$this->email, 
				$this->keyfile
			);
		}
	}

	/** 
	 * OAuth2
	 *
	 * @param $param string
	 * @param $type string
	 * @param $required boolean
	 * @access private
	 * @return mixed
	 */ 
	private function _prep_param($param, $type='string', $required=FALSE)
	{
		if ( $required && (NULL === $param || '' === $param)) 
		{
			return ee()->output->show_user_error('general', 'the ' . $param . ' parameter is required');
		}

		if ($type == 'array' && $param != NULL)
		{
			$param = explode('|', $param);
		}

		if ($type == 'integer' && $param != NULL)
		{
			$param = intval($param);
		}

		// booleans default to FALSE
		if ($type == 'boolean')
		{
			if ($param != NULL)
			{
				$param = (bool) preg_match('/1|on|yes|y/i', $param);
			}
			else
			{
				$param = $default;
			}
		}

		return $param;

	}

	/**
	 * Run a query and generate a report
	 *
	 * @access public
	 * @return string
	 */ 
	public function query()
	{
		// register params
		$report_id 		= $this->_prep_param( ee()->TMPL->fetch_param('ga:report_id', NULL), 'string', TRUE);
		$dimensions 	= $this->_prep_param( ee()->TMPL->fetch_param('ga:dimensions', NULL), 'array', TRUE);
		$metrics 		= $this->_prep_param( ee()->TMPL->fetch_param('ga:metrics', NULL), 'array', TRUE);
		$sort 			= $this->_prep_param( ee()->TMPL->fetch_param('ga:sort', NULL), 'array');
		$filter 		= $this->_prep_param( ee()->TMPL->fetch_param('ga:filter', NULL), 'string');
		$start_date 	= $this->_prep_param( ee()->TMPL->fetch_param('ga:start_date', NULL), 'string');
		$end_date 		= $this->_prep_param( ee()->TMPL->fetch_param('ga:end_date', NULL), 'string');
		$start_index 	= $this->_prep_param( ee()->TMPL->fetch_param('ga:start_index', 1), 'integer');
		$max_results 	= $this->_prep_param( ee()->TMPL->fetch_param('ga:max_results', 10000), 'integer');
		$name 			= $this->_prep_param( ee()->TMPL->fetch_param('name', NULL), 'string', TRUE); // required by Stash

		// attempt to get cached Stash value
		$stash = new Stash(TRUE);

		$list = $stash->get(ee()->TMPL->tagparams);

		if ( empty($list))
		{
			// authorize
			$this->_auth();

			// run query
			self::$ga->requestReportData(
				$report_id,
				$dimensions,
				$metrics,
				$sort,
				$filter,
				$start_date,
				$end_date,
				$start_index,
				$max_results
			);

			// get an array of results
			$data = array();
			$vars = array_merge($metrics, $dimensions);

			$test = self::$ga->getResults();

			foreach(self::$ga->getResults() as $result)
			{
				$row = array();

				foreach($vars as $v) 
				{
					$method  = 'get' . ucfirst($v);
					$row[$v] = $result->{$method}();

					// filter value by regular expression?
					if ($filter = ee()->TMPL->fetch_param('filter:'.$v, FALSE))
					{
						if (preg_match('/^#(.*)#$/', $filter)) {

							preg_match($filter, $row[$v], $found);

				            if (isset($found[1]))
				            {
				                $row[$v] = $found[1];
				            }
						}
					}
				}

				$data[] = $row;
			}

			// set a Stash list
			$list = $stash->flatten_list($data);
			$stash->set(ee()->TMPL->tagparams, $list);
		}

		// get the Stash list
		return $stash->get_list(ee()->TMPL->tagparams, ee()->TMPL->tagdata);
	}


	// usage instructions
	public static function usage() 
	{
  		ob_start();
?>
-------------------
HOW TO USE
-------------------
<ol class="popular col large-col-six">
	{exp:gapifu:query
		name="trending"
		save="yes"
		scope="site"
		replace="no"
		refresh="1440"
		ga:report_id="12345678"
		ga:dimensions="pagePath|pageTitle"
		ga:metrics="uniquePageviews"
		ga:sort="-uniquePageviews"
		ga:filter="pagePath =~ insight/.+/view || pagePath =~ blog/.+/article"
		ga:start_date="7daysAgo"
		ga:end_date="today"
		ga:max_results="8"
		filter:pageTitle="#^(.*): My website \(#"
		filter:pagePath="#^(?:www\.)?mywebsite.com/(.*)#"
	}
 	<li><a href="{site_url}{pagePath}">{pageTitle}</a></li>
 	{/exp:gapifu:query}
</ol>

	<?php
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}	
}