<?php
############################################################################
# Copyright 2013 George Hansper                                            #
# This program has been made available to the Open Source community for    #
# redistribution and further development under the terms of the            #
# GNU General Public License v2: http://www.gnu.org/licenses/gpl-2.0.html  #
############################################################################
# This program is supplied 'as-is', in the hope that it will be useful,    #
# but the author does not make any warranties or guarantees as             #
# to its correct operation.                                                #
#                                                                          #
# Or in other words:                                                       #
#       Test it yourself, and make sure it works for YOU.                  #
############################################################################
# Author: George Hansper                     e-mail:  george@hansper.id.au #
############################################################################
# PNP4Nagios Template: check_cpu.php   (this file)                         #
# For Nagios Plugin:   check_cpu.py                                        #
# Version:  $Id$
############################################################################

#   1st graph: Overall CPU usage with Overall IO Wait
#   2nd graph: CPU usage for each core (up to 8 cores)
#   3rd graph: CPU IO Wait for each core (up to 8 cores)
#   subsequent graphs: repeat of 2nd and 3rd for additional CPU cores if present

# Change the value of $max_cpus_per_graph to suit your taste
# Values higher than 16 will re-use colors from the $cpu_colors list
$max_cpus_per_graph = 8;


$cpu_colors = [ 'c00000','c08000','c0c000','00c000','00a0a0','0000c0','c000c0','808080',
		'e06060','e0a060','e0e060','60e060','60c0c0','6060e0','e060e0','c0c0c0' ];

##########################################################################################
#error_log("names: ". join(" ",array_keys($NAME) ) );
#error_log("values: ". join(" ",array_values($NAME) ) );


$ndx_iowait_total=0;
for ( $ndx=1; $ndx <= count($NAME); $ndx++ ) {
	error_log("$ndx: $NAME[$ndx]");
	if( preg_match("/[0-9]$/",$NAME[$ndx]) ) {
		$ndx_cpus[] = $ndx;
	} elseif ( preg_match("/[0-9]_iowait$/",$NAME[$ndx]) ) {
		$ndx_iowait[] = $ndx;
	} elseif ( preg_match("/[0-9]_steal$/",$NAME[$ndx]) ) {
		$ndx_steal[] = $ndx;
	} elseif ( $NAME[$ndx] == 'cpu_iowait' ) {
		# This is really always 2
		$ndx_iowait_total = $ndx;
	} elseif ( $NAME[$ndx] == 'cpu_steal' ) {
		# This is really always 2
		$ndx_steal_total = $ndx;
	} elseif ( $NAME[$ndx] == 'cpu' ) {
		# This is really always 1
		$ndx_total = $ndx;
	}
}

$cpu_list = preg_grep("/[0-9]$/",$NAME);

$def[1]=""; $opt[1]=""; $ds_name[1]="";
$ds_name[1] = "Total CPU (all cores)";
$opt[1] = "--vertical-label \"cpu percent\" -l0  --title \"Total CPU for $hostname / $servicedesc\" ";

# Graph Total CPU usage and IO Wait (average across all cpu cores)
$def[1]  .= rrd::def("total_cpu",           $RRDFILE[$ndx_total], $DS[$ndx_total], "MAX");
$def[1]  .= rrd::def("total_iowait",        $RRDFILE[$ndx_iowait_total], $DS[$ndx_iowait_total], "MAX");
$def[1]  .= rrd::def("total_steal",         $RRDFILE[$ndx_steal_total],  $DS[$ndx_steal_total],  "MAX");
$def[1]  .= rrd::area("total_cpu",    "#c0c0ff");
$def[1]  .= rrd::area("total_iowait", "#ffa0a0");
$def[1]  .= rrd::area("total_steal",  "#c0c0a0");
$def[1]  .= rrd::line1("total_cpu",   "#0000c0",$NAME[$ndx_total]."\t\t");
$def[1]  .= rrd::gprint("total_cpu", array("LAST", "AVERAGE", "MAX"), "%6.2lf");
$def[1]  .= rrd::line1("total_iowait", "#c00000",$NAME[$ndx_iowait_total]."\t");
$def[1]  .= rrd::gprint("total_iowait", array("LAST", "AVERAGE", "MAX"), "%6.2lf");
$def[1]  .= rrd::line1("total_steal",  "#c0c000",$NAME[$ndx_steal_total]."\t");
$def[1]  .= rrd::gprint("total_iowait", array("LAST", "AVERAGE", "MAX"), "%6.2lf");

if ($WARN[$ndx_total] != "") {
    $def[1] .= "HRULE:".$WARN[$ndx_total]."#FFFF00 ";
}
if ($CRIT[$ndx_total] != "") {
    $def[1] .= "HRULE:".$CRIT[$ndx_total]."#FF0000 ";       
}

$def_n=1;
# Graph Per-Core CPU usage
$color_ndx=0;

for( $cpu_n=0; $cpu_n<count($ndx_cpus); $cpu_n++) {
	if ( $cpu_n % $max_cpus_per_graph == 0 ) {
		# Start a new graph
		$def_n++;
		$cpu_start = $ndx_cpus[$cpu_n];
		if ( $cpu_n + $max_cpus_per_graph >= count($ndx_cpus) ) {
			$cpu_end=$ndx_cpus[count($ndx_cpus)-1];
		} else {
			$cpu_end = $ndx_cpus[$cpu_n + $max_cpus_per_graph - 1];
		}
		$def[$def_n]='';
		$ds_name[$def_n] = "CPU per core";
		$opt[$def_n] = "--vertical-label \"cpu percent\" -l0  --title \"Single-core CPU for ".$NAME[$cpu_start]."-".$NAME[$cpu_end]." $hostname / $servicedesc\" ";
		if ($WARN[$ndx_cpus[0]] != "") {
		    $def[$def_n] .= "HRULE:".$WARN[$ndx_cpus[0]]."#FFFF00 ";
		}
		if ($CRIT[$ndx_cpus[0]] != "") {
		    $def[$def_n] .= "HRULE:".$CRIT[$ndx_cpus[0]]."#FF0000 ";       
		}
	}
	$ndx=$ndx_cpus[$cpu_n];
	$name = $NAME[$ndx];
	$color = $cpu_colors[$color_ndx];
	$def[$def_n]  .= rrd::def("$name",           $RRDFILE[$ndx], $DS[$ndx], "MAX");
	$def[$def_n]  .= rrd::line1("$name", "#$color",$NAME[$ndx]."\t");
	$def[$def_n]  .= rrd::gprint("$name", array("LAST", "AVERAGE", "MAX"), "%6.2lf ");
	if ( $color_ndx == $max_cpus_per_graph -1 ) {
		$def_n++;
	}
	if ( count($ndx_cpus) <= 6 && $color_ndx<4 ) {
		$color_ndx += 2;
	} else {
		$color_ndx++;
	}
	$color_ndx %= min($max_cpus_per_graph,count($cpu_colors));
}


# Graph Per-Core CPU iowait
$color_ndx=0;
for( $cpu_n=0; $cpu_n<count($ndx_cpus); $cpu_n++) {
	if ( $cpu_n % $max_cpus_per_graph == 0 ) {
		# Start a new graph
		$def_n++;
		$cpu_start = $ndx_cpus[$cpu_n];
		if ( $cpu_n + $max_cpus_per_graph >= count($ndx_cpus) ) {
			$cpu_end=$ndx_cpus[count($ndx_cpus)-1];
		} else {
			$cpu_end = $ndx_cpus[$cpu_n + $max_cpus_per_graph - 1];
		}
		$def[$def_n]='';
		$ds_name[$def_n] = "IO Wait per core";
		$opt[$def_n] = "--vertical-label \"io_wait percent\" -l0  --title \"IO Wait per core for $hostname / $servicedesc\" ";
		if ($WARN[$ndx_iowait[0]] != "") {
		    $def[$def_n] .= "HRULE:".$WARN[$ndx_iowait[0]]."#FFFF00 ";
		}
		if ($CRIT[$ndx_iowait[0]] != "") {
		    $def[$def_n] .= "HRULE:".$CRIT[$ndx_iowait[0]]."#FF0000 ";       
		}
	}
	$ndx=$ndx_iowait[$cpu_n];
	$name = $NAME[$ndx];
	$color = $cpu_colors[$color_ndx];
	$def[$def_n]  .= rrd::def("$name",           $RRDFILE[$ndx], $DS[$ndx], "MAX");
	$def[$def_n]  .= rrd::line1("$name", "#$color",$NAME[$ndx]."\t");
	$def[$def_n]  .= rrd::gprint("$name", array("LAST", "AVERAGE", "MAX"), "%6.2lf");
	if ( $color_ndx == $max_cpus_per_graph-1 ) {
		$def_n++;
	}
	if ( count($ndx_cpus) <= 6 && $color_ndx<4 ) {
		$color_ndx += 2;
	} else {
		$color_ndx++;
	}
	$color_ndx %= min($max_cpus_per_graph,count($cpu_colors));
}

# Graph Per-Core CPU steal
$color_ndx=0;
for( $cpu_n=0; $cpu_n<count($ndx_cpus); $cpu_n++) {
	if ( $cpu_n % $max_cpus_per_graph == 0 ) {
		# Start a new graph
		$def_n++;
		$cpu_start = $ndx_cpus[$cpu_n];
		if ( $cpu_n + $max_cpus_per_graph >= count($ndx_cpus) ) {
			$cpu_end=$ndx_cpus[count($ndx_cpus)-1];
		} else {
			$cpu_end = $ndx_cpus[$cpu_n + $max_cpus_per_graph - 1];
		}
		$def[$def_n]='';
		$ds_name[$def_n] = "Steal per core";
		$opt[$def_n] = "--vertical-label \"steal percent\" -l0  --title \"Steal per core for $hostname / $servicedesc\" ";
		if ($WARN[$ndx_steal[0]] != "") {
		    $def[$def_n] .= "HRULE:".$WARN[$ndx_steal[0]]."#FFFF00 ";
		}
		if ($CRIT[$ndx_steal[0]] != "") {
		    $def[$def_n] .= "HRULE:".$CRIT[$ndx_steal[0]]."#FF0000 ";       
		}
	}
	$ndx=$ndx_steal[$cpu_n];
	$name = $NAME[$ndx];
	$color = $cpu_colors[$color_ndx];
	$def[$def_n]  .= rrd::def("$name",           $RRDFILE[$ndx], $DS[$ndx], "MAX");
	$def[$def_n]  .= rrd::line1("$name", "#$color",$NAME[$ndx]."\t");
	$def[$def_n]  .= rrd::gprint("$name", array("LAST", "AVERAGE", "MAX"), "%6.2lf");
	if ( $color_ndx == $max_cpus_per_graph-1 ) {
		$def_n++;
	}
	if ( count($ndx_cpus) <= 6 && $color_ndx<4 ) {
		$color_ndx += 2;
	} else {
		$color_ndx++;
	}
	$color_ndx %= min($max_cpus_per_graph,count($cpu_colors));
}

#error_log($def[1]);
#error_log($def[2]);
#error_log($def[3]);
?>
