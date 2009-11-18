<?php
interface SearchErrorReporting {
	public function getError();
}

class SolrSearch extends SearchEngine implements SearchErrorReporting {

	private $errorCode = null;

	public function __construct() {
		wfLoadExtensionMessages( 'WikiaSearch' );
	}

	public function getError() {
		if($this->errorCode != null) {
			// don't bother with error codes, just display standard error message for now
			return wfMsg( 'wikiasearch-system-error-msg' );
		}
	}

	/**
	 * Perform a full text search query and return a result set.
	 *
	 * @param string $term - Raw search term
	 * @return SolrSearchSet
	 * @access public
	 */
	public function searchText( $term ) {
		global $wgRequest;

		if(empty($term)) {
			return null;
		}

		if(!$wgRequest->getCheck('titlesOnly')) {
			$searchSet = SolrSearchSet::newFromQuery( $term, 'title^7 html', $this->namespaces, $this->limit, $this->offset );
			if($searchSet instanceof SolrSearchSet) {
				return $searchSet;
			}
			else {
				$this->errorCode = $searchSet;
				return null;
			}
		}
		else {
			return null;
		}
	}

	function searchTitle( $term ) {
		global $wgRequest;

		if(empty($term)) {
			return null;
		}

		if($wgRequest->getCheck('titlesOnly')) {
			$searchSet = SolrSearchSet::newFromQuery( $term, 'title', $this->namespaces, $this->limit, $this->offset );
			if($searchSet instanceof SolrSearchSet) {
				return $searchSet;
			}
			else {
				$this->errorCode = $searchSet;
				return null;
			}
		}
		else {
			return null;
		}
	}

	public static function renderExtraRefinements( $refinements ) {
		global $wgRequest;
		$titles = Xml::check( 'titlesOnly', $wgRequest->getCheck('titlesOnly'), array( 'value' => '1', 'id' => 'titlesOnly' ) );
		$titlesLabel = Xml::label( wfMsg( 'wikiasearch-titles-only' ), 'titlesOnly' );
		$refinements = "<p>" . $titles . " " . $titlesLabel . "</p>\n";
		return true;
	}
}

class SolrSearchSet extends SearchResultSet {

	private $mCanonicals = array();

	/**
	 * any query string transformation before sending to backend should be placed here
	 */
	private static function sanitizeQuery($query) {
		// non-indexed number-string phrases issue workaround (RT #24790)
		$query = preg_replace('/(\d+)([a-zA-Z]+)/i', '$1 $2', $query);

		// escape all lucene special characters: + - && || ! ( ) { } [ ] ^ " ~ * ? : \ (RT #25482)
		$query = Apache_Solr_Service::escape($query);

		return $query;
	}

	/**
	 * Contact the solr search server and return a wrapper
	 * object with the set of results. Results may be cached.
	 *
	 * @param string $query
	 * @param int $limit
	 * @param int $offset
	 * @return array
	 * @access public
	 */
	public static function newFromQuery( $query, $queryFields, $namespaces = array(), $limit = 20, $offset = 0 ) {
		global $wgSolrHost, $wgSolrPort, $wgCityId, $wgErrorLog, $wgEnableCrossWikiaSearch, $wgCrossWikiaSearchExcludedWikis;

		$fname = 'SolrSearchSet::newFromQuery';
		wfProfileIn( $fname );

		$solr = new Apache_Solr_Service($wgSolrHost, $wgSolrPort, '/solr');

		$sanitizedQuery = self::sanitizeQuery($query);

		$params = array(
			'fl' => 'title,canonical,url,host,bytes,words,ns,lang,indexed,created,views', // fields we want to fetch back
			'qf' => $queryFields,
			'bf' => 'scale(map(views,10000,100000000,10000),0,10)^20', // force view count to maximum threshold of 10k (make popular articles a level playing field, otherwise main/top pages always win) and scale all views to same scale
			'bq' => '(*:* -html:(' . $sanitizedQuery . '))^20', // boost the inverse set of the content matches again, to make content-only matches at the bottom but still sorted by match
			'qt' => 'dismax',
			'pf' => '', // override defaults
			'mm' => '100%', // "must match" - how many of query clauses (e.g. words) must match
			'ps' => '',
			'tie' => 1, // make it combine all scores instead of picking best match
			'hl' => 'true',
			'hl.fl' => 'html,title', // highlight field
			'hl.snippets' => '2', // number of snippets per field
			'hl.fragsize' => '150', // snippet size in characters
			'hl.simple.pre' => '<span class="searchmatch">',
			'hl.simple.post' => '</span>',
			'indent' => 1,
			'fq' => ''
			//'sort' => 'backlinks desc, views desc, revcount desc, created asc'
		);

		if(count($namespaces)) {
			$nsQuery = '';
			foreach($namespaces as $namespace) {
				$nsQuery .= ( !empty($nsQuery) ? ' OR ' : '' ) . 'ns:' . $namespace;
			}
			$params['fq'] = $nsQuery; // filter results for selected ns
		}

		if( $wgEnableCrossWikiaSearch ) {
			$widQuery = '';
			foreach($wgCrossWikiaSearchExcludedWikis as $wikiId) {
				$widQuery .= ( !empty($widQuery) ? ' AND ' : '' ) . '!wid:' . $wikiId;
			}
			$params['fq'] = ( !empty( $params['fq'] ) ? "(" . $params['fq'] . ") AND " : "" ) . $widQuery . " AND lang:en";
		}
		else {
			$params['fq'] = ( !empty( $params['fq'] ) ? "(" . $params['fq'] . ") AND " : "" ) . "wid:" . $wgCityId;
		}
		//echo "fq=" . $params['fq'] . "<br />";

		try {
			$response = $solr->search($sanitizedQuery, $offset, $limit, $params);
		}
		catch (Exception $exception) {
			//echo '<pre>'; print_r($exception); echo '</pre>';
			$wgErrorLogTmp = $wgErrorLog;
			$wgErrorLog = true;
			Wikia::log( __METHOD__, "ERROR", $exception->getMessage() . " PARAMS: q=$sanitizedQuery, fq=" . $params['fq'] );
			$wgErrorLog = $wgErrorLogTmp;

			wfProfileOut( $fname );
			return 100;
		}
		//echo "<pre>";
		//print_r($response->response);
		//print_r($response->highlighting);
		//exit;

		/*
		$suggestion = null;
		*/

		$resultDocs = $response->response->docs;
		$resultSnippets = is_object($response->highlighting) ? get_object_vars($response->highlighting) : array();
		$resultCount = count($resultDocs);
		$totalHits = $response->response->numFound;

		$resultSet = new SolrSearchSet( $query, $resultDocs, $resultSnippets, $resultCount, $totalHits /*, $suggestion */ );

		wfProfileOut( $fname );
		return $resultSet;
	}

	/**
	 * Private constructor. Use SolrSearchSet::newFromQuery().
	 *
	 * @param string $query
	 * @param array $lines
	 * @param int $resultCount
	 * @param int $totalHits
	 * @param string $suggestion
	 * @access private
	 */
	private function __construct( $query, $results, $snippets, $resultCount, $totalHits = null, $suggestion = null) {
		$this->mQuery             = $query;
		$this->mTotalHits         = $totalHits;
		if(is_array($results)) {
			$this->mResults          = $this->deDupe($results);
		}
		else {
			$this->mResults          = array();
		}
		$this->mSnippets          = $snippets;
		$this->mResultCount       = $resultCount;
		$this->mPos               = 0;
		//$this->mSuggestionQuery   = null;
		//$this->mSuggestionSnippet = '';
		//$this->parseSuggestion($suggestion);
	}

	/**
	 * Remove duplicates (like redirects) from the result set
	 */
	private function deDupe(Array $results) {
		$deDupedResults = array();
		foreach($results as $result) {
			$result->canonical = str_replace('_', ' ', $result->canonical);
			$result->title = str_replace('_', ' ', $result->title);
			if(isset($result->canonical) && !empty($result->canonical)) {
				if(!in_array($result->canonical, $this->mCanonicals)) {
					$this->mCanonicals[] = $result->canonical;
					$deDupedResults[] = $result;
				}
				else {
					continue;
				}
			}
			else if(!in_array($result->title, $this->mCanonicals)) {
				$this->mCanonicals[] = $result->title;
				$deDupedResults[] = $result;
			}
		}
		//echo "<pre>";
		//print_r($deDupedResults);
		//exit;
		return $deDupedResults;
	}
	/**
	 * Some search modes return a total hit count for the query
	 * in the entire article database. This may include pages
	 * in namespaces that would not be matched on the given
	 * settings.
	 *
	 * @return int
	 * @access public
	 */
	function getTotalHits() {
		return $this->mTotalHits;
	}

	function hasResults() {
		return count( $this->mResults ) > 0;
	}

	function numRows() {
		return $this->mResultCount;
	}

	/**
	 * Fetches next search result, or false.
	 * @return LuceneResult
	 * @access public
	 * @abstract
	 */
	function next() {
		if(isset($this->mResults[$this->mPos])) {
			$solrResult = new SolrResult($this->mResults[$this->mPos]);
			$url = $this->mResults[$this->mPos]->url;
			if(isset($this->mSnippets[$url]->html)) {
				$solrResult->setSnippets($this->mSnippets[$url]->html);
			}
			if(isset($this->mSnippets[$url]->title[0])) {
				$solrResult->setHighlightTitle($this->mSnippets[$url]->title[0]);
			}
			$this->mPos++;
		}
		else {
			$solrResult = false;
		}

		return $solrResult;
	}

	/*
	private function parseSuggestion($suggestion) {
		if( is_null($suggestion) )
			return;
		// parse split points and highlight changes
		list($dummy,$points,$sug) = explode(" ",$suggestion);
		$sug = urldecode($sug);
		$points = explode(",",substr($points,1,-1));
		array_unshift($points,0);
		$suggestText = "";
		for($i=1;$i<count($points);$i+=2){
			$suggestText .= htmlspecialchars(substr($sug,$points[$i-1],$points[$i]-$points[$i-1]));
			$suggestText .= '<em>'.htmlspecialchars(substr($sug,$points[$i],$points[$i+1]-$points[$i]))."</em>";
		}
		$suggestText .= htmlspecialchars(substr($sug,end($points)));

		$this->mSuggestionQuery = $this->replaceGenericPrefixes($sug);
		$this->mSuggestionSnippet = $this->replaceGenericPrefixes($suggestText);
	}
	*/
}

class SolrResult extends SearchResult {
	private $mSnippets = array();
	private $mCreated = null;
	private $mIndexed = null;
	private $mHighlightTitle = null;
	private $mRedirectTitle = null;

	/**
	 * Construct a result object from single Apache_Solr_Document object
	 *
	 * @param Apache_Solr_Document $document
	 */
	public function __construct( Apache_Solr_Document $document ) {
		$url = utf8_decode( htmlspecialchars_decode( $document->url ) );
		$title = htmlspecialchars_decode( $document->title );

		if(isset($document->canonical) && !empty($document->canonical)) {
			$canonical = htmlspecialchars_decode($document->canonical);
			$this->mTitle = new SolrResultTitle($document->ns, $canonical, $url);
			$this->mRedirectTitle = new SolrResultTitle($document->ns, $title, $url);
			$this->setHighlightTitle( $canonical );
		}
		else {
			$this->mTitle = new SolrResultTitle($document->ns, $title, $url);
		}

		$this->mWordCount = $document->words;
		$this->mSize = $document->bytes;
		$this->mCreated = isset($document->created) ? $document->created : 0;
		$this->mIndexed = isset($document->indexed) ? $document->indexed : 0;
		$this->mHighlightText = null;
	}

	protected function initText() {
		return true;
	}

	public function setSnippets(Array $snippets) {
		$this->mSnippets = $snippets;
	}

	public function setHighlightTitle($title) {
		if($this->mHighlightTitle == null) {
			$this->mHighlightTitle = str_replace('_', ' ', htmlspecialchars_decode($title));
		}
	}

	public function getTextSnippet($terms = null) {
		if( is_null($this->mHighlightText) ) {
			$this->mHighlightText = '';
			foreach($this->mSnippets as $snippet) {
				$this->mHighlightText .= $snippet . '... ';
			}
		}
		return $this->mHighlightText;
	}

	public function getRedirectTitle() {
		return $this->mRedirectTitle;
	}

	public function getTitleSnippet($terms = null) {
		return ( !is_null($this->mHighlightTitle) ? $this->mHighlightTitle : '' );
	}

	public function isMissingRevision() {
		return false;
	}

	public function getByteSize() {
		return $this->mSize;
	}

	public function getWordCount() {
		return $this->mWordCount;
	}

	public function getTimestamp() {
		return $this->mCreated;
	}

	public static function showHit($link, $redirect, $section, $extract, $data) {
		$data = '';
		return true;
	}

}

/**
 * Simple Title class wrapper for compatibility with Special:Search
 */
class SolrResultTitle extends Title {
	private $mUrl;

	public function __construct($ns, $title, $url) {
		$this->mInterwiki = '';
		$this->mFragment = '';
		$this->mNamespace = 0; //$ns = intval( $ns );
		$this->mDbkeyform = str_replace( ' ', '_', $title );
		$this->mArticleID = 0; //( $ns >= 0 ) ? -1 : 0;
		$this->mUrlform = wfUrlencode( $this->mDbkeyform );
		$this->mTextform = str_replace( '_', ' ', $title );

		$this->mUrl = $this->sanitizeUrl( $url );
	}

	public function getLinkUrl( $query = array(), $variant = false ) {
		return $this->mUrl;
	}

	private function sanitizeUrl($url) {
		// RT #25474
		$url = str_replace( '?', '%3F', $url );
		return $url;
	}
}
