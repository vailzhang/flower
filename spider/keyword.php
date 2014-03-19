<?php
$text = '我是php程序员,偶尔还搞搞IOS的中国人，招聘市场助理，极品ka';

$sh = scws_open ();
scws_set_rule($sh, '/usr/local/scws/etc/rules.utf8.ini');
scws_send_text ( $sh, $text );
scws_set_ignore($sh, true);
scws_set_multi($sh,  SCWS_MULTI_SHORT);
scws_set_duality( $sh, true );
while ($result = scws_get_result ( $sh )) {
	foreach ($result as $i) {
		echo $i ['word'] . "\n";
	}
	//print_r($w) . "\n";
	//scws_free_result($result);
}
scws_close ($sh);