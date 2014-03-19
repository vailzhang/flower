<?php

namespace filter;

class splitWord {
	public function filter($text) {
		$sh = scws_open ();
		scws_send_text ( $sh, $text );
		scws_set_ignore ( $sh, true );
		scws_set_multi ( $sh, SCWS_MULTI_SHORT );
		scws_set_duality ( $sh, false );
		$words = array ();
		while ( $result = scws_get_result ( $sh ) ) {
			foreach ( $result as $item ) {
				if (mb_strlen ( $item ['word'] ) > 1) {
					$words [] = $item ['word'];
				}
			}
		}
		scws_close ( $sh );

		$result = array ();
		while ( $words ) {
			$w = array_pop ( $words );
			foreach ( $words as $key => $word ) {
				if (strpos ( $word, $w ) !== FALSE) {
					unset ( $words [$key] );
				}
			}
			$result [] = $w;
		}		
		return $result;
	}
}