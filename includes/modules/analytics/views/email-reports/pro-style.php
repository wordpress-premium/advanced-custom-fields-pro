<?php
/**
 * Analytics Report email styling.
 *
 * @package    RankMath
 * @subpackage RankMath\Admin
 */

defined( 'ABSPATH' ) || exit;
?>
<style>

	.header {
		background: ###HEADER_BACKGROUND###;
	}

	tr.keywords-table-spacer {
		height: 14px;
	}

	table.stats-table tr.table-heading {
		background: #243B53;
		color: #fff;
		font-weight: 500;
	}

	.stats-table {
		overflow: hidden;
	}

	.stats-table td {
		padding: 10px;
	}

	.stats-table tr:nth-child(2n+1) {
		background: #F0F4F8;
	}

	.stats-table .stat-value {
		font-size: 16px;
	}

	.stats-table .stat-diff, .stats-table .diff-sign {
		font-size: 14px;
	}

	.report-heading {
		margin: 40px 0 20px 0;
	}

	span.post-title, span.post-url {
		display: block;
		max-width: 250px;
	}

	.stats-table a {
		color: #3f77d6;
		font-size: 15px;
		font-weight: 600;
	}

	span.post-url {
		color: #92949f;
		font-size: 14px;
		font-weight: normal;
	}

	###CUSTOM_CSS###
</style>
