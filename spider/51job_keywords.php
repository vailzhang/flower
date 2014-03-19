<?php
require '../../src/Sys/Functions/Common.php';

app ( 'Cli' )->run ();

require path()->src . 'Tool/simple_html_dom.php';

$curl = new \Tool\Curl ();
$curl->setUrl ( 'http://search.51job.com/jobsearch/all_hot_keyword.php' );
$html = $curl->fetch();

$dom = str_get_html($html);
$table = $dom->find('#typeSearchTbl0', 0 );
$list = $table->find('td a');
foreach ($list as $item) {
	// echo $item->
}
echo $table;