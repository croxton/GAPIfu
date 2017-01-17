##GAPIfu v1.0.1

GAPIfu is a wrapper for the [Google Analytics PHP Interface (GAPI)](https://github.com/erebusnz/gapi-google-analytics-php-interface)

It allows you to run queries using the Google Analytics Query API, cache the result for a given time, and output as a Stash list.

### Requirements

* [ExpressionEngine](https://ellislab.com/expressionengine) 2.9+ or 3.1+
* [Stash](https://github.com/croxton/Stash)

### Installation

1. Make sure you already installed [Stash](https://github.com/croxton/Stash).

2. Unzip the download, and then:

	*ExpressionEngine 2* - move the 'gapifu' folder to the `./system/expressionengine/third_party` directory.

	*ExpressionEngine 3* - move the 'gapifu' folder to the `./system/user/addons` directory. Go to Add-On Manager and click the button to install `GAPIfu`.

3. Create a [Google Developers project](https://console.developers.google.com/project)

4. Create service account under this project, [see instructions](https://developers.google.com/identity/protocols/OAuth2ServiceAccount#creatinganaccount)

5. Download the .p12 file for this service account, and upload to a location **above the webroot of your project**

6. Enable 'analytics API' in the [Google Developers console]((https://console.developers.google.com/project))

7. In Google Analytics *Administration > User Management*, give the service account 'Read and Analyse' permissions on the analytics accounts you want to access

8. Open your `./system/expressionengine/config/config.php` file (EE2) or `./system/user/config/config.php` (EE3), add the following lines at the bottom of the file:


```PHP
// the service account email address, 
// e.g. your-project@your-project-gapi.iam.gserviceaccount.com
$config['gapifu_email'] = ''; 

// the path to your key file
// e.g. /home/mywebsite/your-project-GAPI-a1bcdef34g56.p12
$config['gapifu_keyfile'] = '';
```


### How to use

	{!-- 8 most popular articles, cache for 1 day --}
	<ol class="popular-articles">
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
	

### Parameters

#### Stash parameters

Supports Stash [set_list](https://github.com/croxton/Stash/wiki/%7Bexp:stash:set_list%7D) AND [get_list](https://github.com/croxton/Stash/wiki/%7Bexp:stash:get_list%7D) parameters.
The `name=""` parameter is required.

#### GAPI parameters
**Tip:** Use the [Google Analytics Query Explorer](https://ga-dev-tools.appspot.com/query-explorer/) to work out the parameter values for your specific query.

You should omit the `ga:` prefix in the values passed for these parameters:

##### ga:report_id
*(required)* The unique Google Analytics profile ID.

##### ga:dimensions
*(required)* The dimensions parameter breaks down metrics by common criteria; for example, by `pagePath` or `pageTitle`.

##### ga:metrics

*(required)* The aggregated statistics for user activity to your site, such as clicks or pageviews. If a query has no dimensions parameter, the returned metrics provide aggregate values for the requested date range, such as overall pageviews or total bounces. However, when dimensions are requested, values are segmented by dimension value. For example, `pageviews` requested with `country` returns the total pageviews per country.

##### ga:sort

*(optional)* A list of metrics and dimensions indicating the sorting order and sorting direction for the returned data.

##### ga:filters

*(optional)* The filters query string parameter restricts the data returned from your request. To use the filters parameter, supply a dimension or metric on which to filter, followed by the filter expression. E.g. to return articles under the `blog/articles` path only:
	
	ga:filter="pagePath =~ blog/article"
	
##### ga:start_date	

*(required)* The start of the date range to query. Date values can be for a specific date by using the pattern `YYYY-MM-DD` or relative by using `today`, `yesterday`, or the `NdaysAgo` pattern. Values must match `[0-9]{4}-[0-9]{2}-[0-9]{2}|today|yesterday|[0-9]+(daysAgo)`. 

##### ga:end_date
*(required)* The end of the date range to query.

##### ga:max_results
*(optional, defaults to 10,000)* The maximum number of results to return. You should specify a low number here to avoid using up your daily quota.

#### Filter parameters

You can optionally filter the returned values of any of the requested metrics or dimensions with a regular expression. Simply prefix your metrix or dimension name with `filter:` and include a capture group in your regular expression.

E.g., to remove the full URL to your website from the values returned for the `pagePath` dimension:

	filter:pagePath="#^(?:www\.)?mywebsite.com/(.*)#"


### Variables

Any metrics and dimensions requested in your query are avaliable as list variables inside the tag pair, e.g. `{pagePath}`.