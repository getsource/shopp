<?php

class SalesReport extends ShoppReportFramework implements ShoppReport {

	function query () {
		global $bbdb;
		extract($this->options, EXTR_SKIP);
		$data =& $this->data;

		$where = array();

		$where[] = "$starts < UNIX_TIMESTAMP(o.created)";
		$where[] = "$ends > UNIX_TIMESTAMP(o.created)";

		$where = join(" AND ",$where);
		$id = $this->timecolumn('o.created');
		$orders_table = DatabaseObject::tablename('purchase');
		$purchased_table = DatabaseObject::tablename('purchased');
		$query = "SELECT CONCAT($id) AS id,
							UNIX_TIMESTAMP(o.created) as ts,
							COUNT(DISTINCT p.id) AS items,
							COUNT(DISTINCT o.id) AS orders,
							SUM(o.subtotal) as subtotal,
							SUM(o.tax) as tax,
							SUM(o.shipping) as shipping,
							SUM(o.discount) as discounts,
							SUM(o.total) as total,
							AVG(o.total) AS avgorder,
							AVG(p.unitprice) AS avgitem
					FROM $purchased_table AS p
					LEFT JOIN $orders_table AS o ON p.purchase=o.id
					WHERE $where
					GROUP BY CONCAT($id)";

		$data = DB::query($query,'array','index','id');

	}

	function setup () {
		extract($this->options, EXTR_SKIP);

		$this->Chart = new ShoppReportChart();
		$Chart = $this->Chart;
		$Chart->series(0,__('Orders','Shopp'));

		// Post processing for stats to fill in date ranges
		$data =& $this->data;
		$stats =& $this->report;

		$month = date('n',$starts); $day = date('j',$starts); $year = date('Y',$starts);

		$range = $this->range($starts,$ends,$scale);
		$Chart->timeaxis('xaxis',$range,$scale);

		$this->total = $range;
		$stats = array_fill(0,$range,true);

		foreach ($stats as $i => &$s) {
			$s = new StdClass();
			$s->ts = $s->orders = $s->items = 0;

			$index = $i;
			switch (strtolower($scale)) {
				case 'hour': $ts = mktime($i,0,0,$month,$day,$year); break;
				case 'week':
					$ts = mktime(0,0,0,$month,$day+($i*7),$year);
					$index = sprintf('%s %s',(int)date('W',$ts),date('Y',$ts));
					break;
				case 'month':
					$ts = mktime(0,0,0,$month+$i,1,$year);
					$index = sprintf('%s %s',date('n',$ts),date('Y',$ts));
					break;
				case 'year':
					$ts = mktime(0,0,0,1,1,$year+$i);
					$index = sprintf('%s',date('Y',$ts));
					break;
				default:
					$ts = mktime(0,0,0,$month,$day+$i,$year);
					$index = sprintf('%s %s %s',date('j',$ts),date('n',$ts),date('Y',$ts));
					break;
			}

			if (isset($data[$index])) {
				$props = get_object_vars($data[$index]);
				foreach ( $props as $property => $value)
					$s->$property = $value;
			}
			$s->ts = $ts;

			$Chart->data(0,$s->ts,(int)$s->orders);
		}

	}

	function columns () {
		ShoppUI::register_column_headers($this->screen,array(
			'period'=>__('Period','Shopp'),
			'numorders'=>__('# of Orders','Shopp'),
			'items'=>__('Items Ordered','Shopp'),
			'subtotal'=>__('Subtotal','Shopp'),
			'tax'=>__('Tax','Shopp'),
			'shipping'=>__('Shipping','Shopp'),
			'discounts'=>__('Discounts','Shopp'),
			'total'=>__('Total','Shopp'),
			'orderavg'=>__('Average Order','Shopp'),
			'itemavg'=>__('Average Items','Shopp')
		));
	}

	function period ($data,$column,$coltitle) {
		switch (strtolower($this->options['op'])) {
			case 'hour': echo date('ga',$data->ts); break;
			case 'day': echo date('l, F j, Y',$data->ts); break;
			case 'week': echo $this->weekrange($data->ts); break;
			case 'month': echo date('F Y',$data->ts); break;
			case 'year': echo date('Y',$data->ts); break;
			default: echo $data->ts; break;
		}
	}

	function numorders ($data) { echo $data->orders; }

	function items ($data) { echo $data->items; }

	function subtotal ($data) { echo money($data->subtotal); }

	function tax ($data) { echo money($data->tax); }

	function shipping ($data) { echo money($data->shipping); }

	function discounts ($data) { echo money($data->discounts); }

	function total ($data) { echo money($data->total); }

	function orderavg ($data) { echo money($data->avgorder); }

	function itemavg ($data) { echo money($data->avgitem); }

}