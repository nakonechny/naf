<?php

namespace naf\math;

class Math {
	
	/**
	 * Greatest Common Denominator
	 *
	 * @param int $m
	 * @param int $n
	 * @return int
	 */
	static function gcd($m, $n)
	{
		$m = abs($m);
		$n = abs($n);
		if ((0 == $m) || (0 == $n)) return $m + $n;
	
		while ($m != $n) {
			if ($m > $n)
			$m -= $n;
			else
			$n -= $m;
		}
	
		return abs($n);
	}
}