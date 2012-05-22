<?php

/**
 * Pager.
 */

namespace naf\util;

use \Naf;
use \Iterator;

class Pager implements Iterator {
	
	const AUTORESOLVE = -1;

	/**
	 * row count
	 *
	 * @var int
	 */
	private $rows;
	
	/**
	 * @var int
	 */
	private $pageSize, $pageNumber;
	
	/**
	 * @var int
	 */
	private $pageCount, $maxDisplayedPages = 10;
	
	/**
	 * View script that renders this pager instance
	 *
	 * @var string
	 */
	private $template = "pager";
	
	/**
	 * URL query params
	 *
	 * @var array
	 */
	private $queryParams;
	
	/**
	 * Separator for query string arguments
	 *
	 * @var string
	 */
	private $separator = "&amp;";
	
	/**
	 * Anchor
	 *
	 * @var string
	 */
	private $anchor;
	
	/**
	 * for iterating through pages
	 *
	 * @var int
	 */
	private $current = 1, $start, $end;
	
	/**
	 * Whether all required calculations are already done
	 *
	 * @var bool
	 */
	private $finalized = false;
	
	/**
	 * URL format to use in sprintf
	 *
	 * @var string
	 */
	private $fmt;
	
	/**
	 * Constructor
	 *
	 * @param int $rows
	 * @param int $pageNumber defaults to 1
	 * @param int $pageSize defaults to 20
	 */
	function __construct($rows, $pageNumber = self::AUTORESOLVE, $pageSize = null)
	{
		$this->rows = (int) $rows;
		
		if (self::AUTORESOLVE === $pageNumber)
		{
			$pageNumber = self::resolvePageNumber();
		}
		if (1 > (int) $pageNumber) $pageNumber = 1;
		if (1 > (int) $pageSize) $pageSize = 20;
		
		$this->pageNumber = $pageNumber;
		$this->pageSize = $pageSize;
		
		$this->queryParams = &$_GET;// we need to reference so that changes to $_GET affect us.
	}
	
	/**
	 * Automagically resolve page number
	 *
	 * @return int
	 */
	static function resolvePageNumber()
	{
		$pageNumber = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, array('options' => array('min_range' => 1)));
		
		return ($pageNumber ? $pageNumber : 1);
	}
	
	/**
	 * Sometimes it is necessary to preserve the anchor part of the URL
	 *
	 * @param string $anchor
	 */
	function setAnchor($anchor)
	{
		$this->anchor = $anchor;
	}
	/**
	 * get the anchor part of the URL
	 *
	 * @return string
	 */
	function getAnchor()
	{
		return $this->anchor ? "#" . $this->anchor : '';
	}
	
	/**
	 * Render pager.
	 *
	 * @param object $view object of a class supporting render($template) method
	 * @return void
	 */
	function render($view)
	{
		$this->finalize();
		$view->pager = $this;
		return $view->render($this->template);
	}
	
	function getPageNumber()
	{
		return $this->pageNumber;
	}
	
	function getPageSize()
	{
		return $this->pageSize;
	}
	
	function getStart()
	{
		return (($this->pageNumber - 1) * $this->pageSize) + 1;
	}
	
	function getEnd()
	{
		return min($this->getStart() + $this->pageSize - 1, $this->rows);
	}
	
	function setFormat($fmt)
	{
		$this->fmt = $fmt;
	}
	
	function getFormat()
	{
		if (null === $this->fmt)
		{
			$uri = $_SERVER['REQUEST_URI'];
			$pos = strpos($uri, '?');
			if ($pos) {
				$path = substr($uri, 0, $pos);
			} else {
				$path = $uri;
			}
			
			$this->fmt = $path;
			$params = $this->queryParams;
			if (isset($params['page']))
			{
				unset($params['page']);
			}
			if (count($params))
			{
				$this->fmt .= str_replace('%', '%%', '?' . http_build_query($params) . $this->separator );
				$this->fmt .= 'page=%d';
			} else {
				$this->fmt .= '?page=%d';
			}
			
			$this->fmt .= $this->getAnchor();
		}
		
		return $this->fmt;
	}
	
	/**
	 * Generate URL
	 *
	 * @param int $page
	 * @return string
	 */
	function url($page)
	{
		return sprintf($this->getFormat(), $page);
	}
	
	/**
	 * Are we in the very first page?
	 *
	 * @return bool
	 */
	function first()
	{
		return 1 >= $this->pageNumber;
	}
	/**
	 * Are we in the very last page?
	 *
	 * @return bool
	 */
	function last()
	{
		return $this->pageNumber >= $this->pageCount;
	}
	
	/**
	 * @return int
	 */
	function pageCount()
	{
		return $this->pageCount;
	}
	
	/**
	 * @return int
	 */
	function previousPage()
	{
		return $this->pageNumber - 1;
	}
	
	/**
	 * @return int
	 */
	function nextPage()
	{
		return $this->pageNumber + 1;
	}
	
	/**
	 * Is current page selected (use it in Iterator cycle)
	 * 
	 * @return bool
	 */
	function selected()
	{
		return $this->current == $this->pageNumber;
	}
	
	/**
	 * @param int $max
	 */
	function setMaxDisplayedPages($max)
	{
		$this->maxDisplayedPages = $max;
	}
	
	private function calculatePageCount()
	{
		return ceil($this->rows/$this->pageSize);
	}
	
	/**
	 * Iterator methods
	 */
	
	function current()
	{
		return $this->url($this->current);
	}
	function next()
	{
		++$this->current;
	}
	function valid()
	{
		return $this->current <= $this->end;
	}
	function key()
	{
		return $this->current;
	}
	function rewind()
	{
		$this->current = $this->start;
	}
	
	function finalize()
	{
		if ($this->finalized)
		{
			return ;
		}
		
		$this->pageCount = $this->calculatePageCount();
		
		if ($this->maxDisplayedPages > 0)
		{
			$this->start = (floor(($this->pageNumber - 1)/ $this->maxDisplayedPages) * $this->maxDisplayedPages) + 1;
			$this->end = min($this->pageCount, $this->start + $this->maxDisplayedPages - 1);
			$this->start = max(1, min($this->start, $this->end - floor($this->maxDisplayedPages / 2)));
		}
		else
		{
			$this->start = 1;
			$this->end = $this->pageCount;
		}
		
		$this->current = $this->start;
		
		$this->finalized = true;
	}
}