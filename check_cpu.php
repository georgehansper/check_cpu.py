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

# Version 1.7 $Id$

$max_cpus_per_graph = 8;


function cpu_color($cpu_n,$cpus_per_graph) {
	$cpu_colors  = [ 'c00000','c08000','c0c000','00a000','00a0a0','0000c0','c000c0','808080',
			 'e06060','e0a060','e0e060','60e060','60c0c0','6060e0','e060e0','c0c0c0' ];
	$cpu_colors6 = [ 'c00000','c0c000','00a000','0000c0','c000c0','808080' ];
	
	$color_ndx = $cpu_n % $cpus_per_graph;
	if ( $cpus_per_graph <= 6 ) {
		return($cpu_colors6[$color_ndx]);
	} else {
		return($cpu_colors[$color_ndx]);
	}
}
##########################################################################################
#error_log("names: ". join(" ",array_keys($NAME) ) );
#error_log("values: ". join(" ",array_values($NAME) ) );

for ( $ndx=1; $ndx <= count($NAME); $ndx++ ) {
	#error_log("$ndx: $NAME[$ndx]");
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
		# This is really always 3
		$ndx_steal_total = $ndx;
	} elseif ( $NAME[$ndx] == 'cpu' ) {
		# This is really always 1
		$ndx_total = $ndx;
	} elseif( preg_match("/[0-9].all$/",$NAME[$ndx]) ) {
		$ndx_cpu_ticks[] = $ndx;
	} elseif( preg_match("/[0-9].busy$/",$NAME[$ndx]) ) {
		$ndx_busy_ticks[] = $ndx;
	} elseif ( preg_match("/[0-9].iowait$/",$NAME[$ndx]) ) {
		$ndx_iowait_ticks[] = $ndx;
	} elseif ( preg_match("/[0-9].steal$/",$NAME[$ndx]) ) {
		$ndx_steal_ticks[] = $ndx;
	} elseif ( $NAME[$ndx] == 'cpu.all' ) {
		$ndx_total_cpu_ticks = $ndx;
	} elseif ( $NAME[$ndx] == 'cpu.busy' ) {
		$ndx_total_busy_ticks = $ndx;
	} elseif ( $NAME[$ndx] == 'cpu.iowait' ) {
		$ndx_total_iowait_ticks = $ndx;
	} elseif ( $NAME[$ndx] == 'cpu.steal' ) {
		$ndx_total_steal_ticks = $ndx;
	} elseif ( $NAME[$ndx] == 'procs' ) {
		$ndx_processes = $ndx;
	} elseif ( $NAME[$ndx] == 'ctxt' ) {
		$ndx_ctxt = $ndx;
	}
}

$cpu_list = preg_grep("/[0-9]$/",$NAME);

$def_n=1;

if ( isset($ndx_total) ) {
	$def[$def_n]=""; $opt[$def_n]=""; $ds_name[$def_n]="";
	$ds_name[$def_n] = "Total CPU (all cores)";
	$opt[$def_n] = "--vertical-label \"cpu percent\" -l0  --title \"Total CPU for $hostname / $servicedesc\" ";

	# Graph Total CPU usage and IO Wait (average across all cpu cores)
	$def[$def_n]  .= rrd::def("total_cpu",           $RRDFILE[$ndx_total], $DS[$ndx_total], "MAX");
	$def[$def_n]  .= rrd::def("total_iowait",        $RRDFILE[$ndx_iowait_total], $DS[$ndx_iowait_total], "MAX");
	$def[$def_n]  .= rrd::def("total_steal",         $RRDFILE[$ndx_steal_total],  $DS[$ndx_steal_total],  "MAX");
	$def[$def_n]  .= rrd::area("total_cpu",    "#c0c0ff");
	$def[$def_n]  .= rrd::area("total_iowait", "#ffa0a0");
	$def[$def_n]  .= rrd::area("total_steal",  "#c0c0a0");
	$def[$def_n]  .= rrd::line1("total_cpu",   "#0000c0",$NAME[$ndx_total]."\t\t");
	$def[$def_n]  .= rrd::gprint("total_cpu", array("LAST", "AVERAGE", "MAX"), "%6.2lf");
	$def[$def_n]  .= rrd::line1("total_iowait", "#c00000",$NAME[$ndx_iowait_total]."\t");
	$def[$def_n]  .= rrd::gprint("total_iowait", array("LAST", "AVERAGE", "MAX"), "%6.2lf");
	$def[$def_n]  .= rrd::line1("total_steal",  "#c0c000",$NAME[$ndx_steal_total]."\t");
	$def[$def_n]  .= rrd::gprint("total_steal", array("LAST", "AVERAGE", "MAX"), "%6.2lf");

	if ($WARN[$ndx_total] != "") {
	    $def[$def_n] .= "HRULE:".$WARN[$ndx_total]."#FFFF00 ";
	}
	if ($CRIT[$ndx_total] != "") {
	    $def[$def_n] .= "HRULE:".$CRIT[$ndx_total]."#FF0000 ";       
	}
}   #if ( isset($ndx_total) )

# Graph Per-Core CPU usage
if ( isset( $ndx_cpus ) ) {
	$cpus_per_graph = min($max_cpus_per_graph, count($ndx_cpus));
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
		$color = cpu_color($cpu_n,$cpus_per_graph);
		$def[$def_n]  .= rrd::def("$name",           $RRDFILE[$ndx], $DS[$ndx], "MAX");
		$def[$def_n]  .= rrd::line1("$name", "#$color",$NAME[$ndx]."\t");
		$def[$def_n]  .= rrd::gprint("$name", array("LAST", "AVERAGE", "MAX"), "%6.2lf ");
	}
}


# Graph Per-Core CPU iowait
if ( isset($ndx_iowait) ) {
	$cpus_per_graph = min($max_cpus_per_graph, count($ndx_iowait));
	for( $cpu_n=0; $cpu_n<count($ndx_iowait); $cpu_n++) {
		if ( $cpu_n % $max_cpus_per_graph == 0 ) {
			# Start a new graph
			$def_n++;
			$cpu_start = $ndx_iowait[$cpu_n];
			if ( $cpu_n + $max_cpus_per_graph >= count($ndx_iowait) ) {
				$cpu_end=$ndx_iowait[count($ndx_iowait)-1];
			} else {
				$cpu_end = $ndx_iowait[$cpu_n + $max_cpus_per_graph - 1];
			}
			$def[$def_n]='';
			$ds_name[$def_n] = "IO Wait per core";
			$opt[$def_n] = "--vertical-label \"io_wait percent\" -l0  --title \"IO Wait per core for ".$NAME[$cpu_start]."-".$NAME[$cpu_end]." $hostname / $servicedesc\" ";
			if ($WARN[$ndx_iowait[0]] != "") {
			    $def[$def_n] .= "HRULE:".$WARN[$ndx_iowait[0]]."#FFFF00 ";
			}
			if ($CRIT[$ndx_iowait[0]] != "") {
			    $def[$def_n] .= "HRULE:".$CRIT[$ndx_iowait[0]]."#FF0000 ";       
			}
		}
		$ndx=$ndx_iowait[$cpu_n];
		$name = $NAME[$ndx];
		$color = cpu_color($cpu_n,$cpus_per_graph);
		$def[$def_n]  .= rrd::def("$name",           $RRDFILE[$ndx], $DS[$ndx], "MAX");
		$def[$def_n]  .= rrd::line1("$name", "#$color",$NAME[$ndx]."\t");
		$def[$def_n]  .= rrd::gprint("$name", array("LAST", "AVERAGE", "MAX"), "%6.2lf");
	}
}

# Graph Per-Core CPU steal
if ( isset($ndx_steal) ) {
	$cpus_per_graph = min($max_cpus_per_graph, count($ndx_steal));
	for( $cpu_n=0; $cpu_n<count($ndx_steal); $cpu_n++) {
		if ( $cpu_n % $max_cpus_per_graph == 0 ) {
			# Start a new graph
			$def_n++;
			$cpu_start = $ndx_steal[$cpu_n];
			if ( $cpu_n + $max_cpus_per_graph >= count($ndx_steal) ) {
				$cpu_end=$ndx_steal[count($ndx_steal)-1];
			} else {
				$cpu_end = $ndx_steal[$cpu_n + $max_cpus_per_graph - 1];
			}
			$def[$def_n]='';
			$ds_name[$def_n] = "Steal per core";
			$opt[$def_n] = "--vertical-label \"steal percent\" -l0  --title \"Steal per core for ".$NAME[$cpu_start]."-".$NAME[$cpu_end]." $hostname / $servicedesc\" ";
			if ($WARN[$ndx_steal[0]] != "") {
			    $def[$def_n] .= "HRULE:".$WARN[$ndx_steal[0]]."#FFFF00 ";
			}
			if ($CRIT[$ndx_steal[0]] != "") {
			    $def[$def_n] .= "HRULE:".$CRIT[$ndx_steal[0]]."#FF0000 ";       
			}
		}
		$ndx=$ndx_steal[$cpu_n];
		$name = $NAME[$ndx];
		$color = cpu_color($cpu_n,$cpus_per_graph);
		$def[$def_n]  .= rrd::def("$name",           $RRDFILE[$ndx], $DS[$ndx], "MAX");
		$def[$def_n]  .= rrd::line1("$name", "#$color",$NAME[$ndx]."\t");
		$def[$def_n]  .= rrd::gprint("$name", array("LAST", "AVERAGE", "MAX"), "%6.2lf");
	}
}

##################################################################
# Graph *_ticks counters
##################################################################
# Graph Total CPU usage and IO Wait (average across all cpu cores)
if ( isset($ndx_total_cpu_ticks) ) {
	$def_n++;
	$def[$def_n]=""; $opt[$def_n]=""; $ds_name[$def_n]="";
	$ds_name[$def_n] = "CPU usage in ticks/second (all cores)";
	$opt[$def_n] = "--vertical-label \"cpu ticks/s\" -l0  --title \"CPU usage in ticks for $hostname / $servicedesc\" ";

	$def[$def_n]  .= rrd::def("total_cpu_ticks",           $RRDFILE[$ndx_total_cpu_ticks], $DS[$ndx_total_cpu_ticks], "MAX");
	$def[$def_n]  .= rrd::def("total_busy_ticks",           $RRDFILE[$ndx_total_busy_ticks], $DS[$ndx_total_busy_ticks], "MAX");
	$def[$def_n]  .= rrd::def("total_iowait_ticks",        $RRDFILE[$ndx_total_iowait_ticks], $DS[$ndx_total_iowait_ticks], "MAX");
	$def[$def_n]  .= rrd::def("total_steal_ticks",         $RRDFILE[$ndx_total_steal_ticks],  $DS[$ndx_total_steal_ticks],  "MAX");

	$def[$def_n]  .= rrd::area("total_cpu_ticks",    "#f0f0f0");
	$def[$def_n]  .= rrd::area("total_busy_ticks",    "#c0c0ff");
	$def[$def_n]  .= rrd::area("total_iowait_ticks", "#ffa0a0");
	$def[$def_n]  .= rrd::area("total_steal_ticks",  "#c0c0a0");

	$def[$def_n]  .= rrd::line1("total_cpu_ticks",   "#000000",$NAME[$ndx_total_cpu_ticks]."\t\t");
	$def[$def_n]  .= rrd::gprint("total_cpu_ticks", array("LAST", "AVERAGE", "MAX"), "%6.2lf");
	$def[$def_n]  .= rrd::line1("total_busy_ticks",   "#0000c0",$NAME[$ndx_total_busy_ticks]."\t\t");
	$def[$def_n]  .= rrd::gprint("total_busy_ticks", array("LAST", "AVERAGE", "MAX"), "%6.2lf");
	$def[$def_n]  .= rrd::line1("total_iowait_ticks", "#c00000",$NAME[$ndx_total_iowait_ticks]."\t");
	$def[$def_n]  .= rrd::gprint("total_iowait_ticks", array("LAST", "AVERAGE", "MAX"), "%6.2lf");
	$def[$def_n]  .= rrd::line1("total_steal_ticks",  "#c0c000",$NAME[$ndx_total_steal_ticks]."\t");
	$def[$def_n]  .= rrd::gprint("total_steal_ticks", array("LAST", "AVERAGE", "MAX"), "%6.2lf");
}

# Graph Per-Core CPU total ticks - based on cpu ticks (aka jiffies)
if ( isset($ndx_cpu_ticks) ) {
	$cpus_per_graph = min($max_cpus_per_graph, count($ndx_cpu_ticks));
	for( $cpu_n=0; $cpu_n<count($ndx_cpu_ticks); $cpu_n++) {
		if ( $cpu_n % $max_cpus_per_graph == 0 ) {
			# Start a new graph
			$def_n++;
			$cpu_start = $ndx_cpu_ticks[$cpu_n];
			if ( $cpu_n + $max_cpus_per_graph >= count($ndx_cpu_ticks) ) {
				$cpu_end=$ndx_cpu_ticks[count($ndx_cpu_ticks)-1];
			} else {
				$cpu_end = $ndx_cpu_ticks[$cpu_n + $max_cpus_per_graph - 1];
			}
			$def[$def_n]='';
			$ds_name[$def_n] = "CPU total ticks, per core";
			$opt[$def_n] = "--vertical-label \"cpu ticks\" -l0  --title \"CPU total ticks for cores ".$NAME[$cpu_start]."-".$NAME[$cpu_end]." $hostname / $servicedesc\" ";
			if ($WARN[$ndx_cpu_ticks[0]] != "") {
			    $def[$def_n] .= "HRULE:".$WARN[$ndx_cpu_ticks[0]]."#FFFF00 ";
			}
			if ($CRIT[$ndx_cpu_ticks[0]] != "") {
			    $def[$def_n] .= "HRULE:".$CRIT[$ndx_cpu_ticks[0]]."#FF0000 ";       
			}
		}
		$ndx=$ndx_cpu_ticks[$cpu_n];
		$name = str_replace('.','__',$NAME[$ndx]);
		$color = cpu_color($cpu_n,$cpus_per_graph);
		$def[$def_n]  .= rrd::def("$name",           $RRDFILE[$ndx], $DS[$ndx], "MAX");
		$def[$def_n]  .= rrd::line1("$name", "#$color",$NAME[$ndx]."\t");
		$def[$def_n]  .= rrd::gprint("$name", array("LAST", "AVERAGE", "MAX"), "%6.2lf ");
	}
}

# Graph Per-Core CPU usage - based on cpu ticks (aka jiffies)
if ( isset($ndx_busy_ticks) ) {
	$cpus_per_graph = min($max_cpus_per_graph, count($ndx_busy_ticks));
	for( $cpu_n=0; $cpu_n<count($ndx_busy_ticks); $cpu_n++) {
		if ( $cpu_n % $max_cpus_per_graph == 0 ) {
			# Start a new graph
			$def_n++;
			$cpu_start = $ndx_busy_ticks[$cpu_n];
			if ( $cpu_n + $max_cpus_per_graph >= count($ndx_busy_ticks) ) {
				$cpu_end=$ndx_busy_ticks[count($ndx_busy_ticks)-1];
			} else {
				$cpu_end = $ndx_busy_ticks[$cpu_n + $max_cpus_per_graph - 1];
			}
			$def[$def_n]='';
			$ds_name[$def_n] = "CPU busy ticks, per core";
			$opt[$def_n] = "--vertical-label \"cpu ticks\" -l0  --title \"CPU busy ticks for cores ".$NAME[$cpu_start]."-".$NAME[$cpu_end]." $hostname / $servicedesc\" ";
			if ($WARN[$ndx_busy_ticks[0]] != "") {
			    $def[$def_n] .= "HRULE:".$WARN[$ndx_busy_ticks[0]]."#FFFF00 ";
			}
			if ($CRIT[$ndx_busy_ticks[0]] != "") {
			    $def[$def_n] .= "HRULE:".$CRIT[$ndx_busy_ticks[0]]."#FF0000 ";       
			}
		}
		$ndx=$ndx_busy_ticks[$cpu_n];
		$name = str_replace('.','__',$NAME[$ndx]);
		$color = cpu_color($cpu_n,$cpus_per_graph);
		$def[$def_n]  .= rrd::def("$name",           $RRDFILE[$ndx], $DS[$ndx], "MAX");
		$def[$def_n]  .= rrd::line1("$name", "#$color",$NAME[$ndx]."\t");
		$def[$def_n]  .= rrd::gprint("$name", array("LAST", "AVERAGE", "MAX"), "%6.2lf ");
	}
}

# Graph Per-Core CPU iowait
if ( isset($ndx_iowait_ticks) ) {
	$cpus_per_graph = min($max_cpus_per_graph, count($ndx_iowait_ticks));
	for( $cpu_n=0; $cpu_n<count($ndx_iowait_ticks); $cpu_n++) {
		if ( $cpu_n % $max_cpus_per_graph == 0 ) {
			# Start a new graph
			$def_n++;
			$cpu_start = $ndx_iowait_ticks[$cpu_n];
			if ( $cpu_n + $max_cpus_per_graph >= count($ndx_iowait_ticks) ) {
				$cpu_end=$ndx_iowait_ticks[count($ndx_iowait_ticks)-1];
			} else {
				$cpu_end = $ndx_iowait_ticks[$cpu_n + $max_cpus_per_graph - 1];
			}
			$def[$def_n]='';
			$ds_name[$def_n] = "IO Wait per core";
			$opt[$def_n] = "--vertical-label \"io_wait percent\" -l0  --title \"IO Wait per core for ".$NAME[$cpu_start]."-".$NAME[$cpu_end]." $hostname / $servicedesc\" ";
			if ($WARN[$ndx_iowait_ticks[0]] != "") {
			    $def[$def_n] .= "HRULE:".$WARN[$ndx_iowait_ticks[0]]."#FFFF00 ";
			}
			if ($CRIT[$ndx_iowait_ticks[0]] != "") {
			    $def[$def_n] .= "HRULE:".$CRIT[$ndx_iowait_ticks[0]]."#FF0000 ";       
			}
		}
		$ndx=$ndx_iowait_ticks[$cpu_n];
		$name = str_replace('.','__',$NAME[$ndx]);
		$color = cpu_color($cpu_n,$cpus_per_graph);
		$def[$def_n]  .= rrd::def("$name",           $RRDFILE[$ndx], $DS[$ndx], "MAX");
		$def[$def_n]  .= rrd::line1("$name", "#$color",$NAME[$ndx]."\t");
		$def[$def_n]  .= rrd::gprint("$name", array("LAST", "AVERAGE", "MAX"), "%6.2lf");
	}
}

# Graph Per-Core CPU steal
if ( isset($ndx_steal_ticks) ) {
	$cpus_per_graph = min($max_cpus_per_graph, count($ndx_steal_ticks));
	for( $cpu_n=0; $cpu_n<count($ndx_steal_ticks); $cpu_n++) {
		if ( $cpu_n % $max_cpus_per_graph == 0 ) {
			# Start a new graph
			$def_n++;
			$cpu_start = $ndx_steal_ticks[$cpu_n];
			if ( $cpu_n + $max_cpus_per_graph >= count($ndx_steal_ticks) ) {
				$cpu_end=$ndx_steal_ticks[count($ndx_steal_ticks)-1];
			} else {
				$cpu_end = $ndx_steal_ticks[$cpu_n + $max_cpus_per_graph - 1];
			}
			$def[$def_n]='';
			$ds_name[$def_n] = "Steal per core";
			$opt[$def_n] = "--vertical-label \"steal percent\" -l0  --title \"Steal per core for ".$NAME[$cpu_start]."-".$NAME[$cpu_end]." $hostname / $servicedesc\" ";
			if ($WARN[$ndx_steal_ticks[0]] != "") {
			    $def[$def_n] .= "HRULE:".$WARN[$ndx_steal_ticks[0]]."#FFFF00 ";
			}
			if ($CRIT[$ndx_steal_ticks[0]] != "") {
			    $def[$def_n] .= "HRULE:".$CRIT[$ndx_steal_ticks[0]]."#FF0000 ";       
			}
		}
		$ndx=$ndx_steal_ticks[$cpu_n];
		$name = str_replace('.','__',$NAME[$ndx]);
		$color = cpu_color($cpu_n,$cpus_per_graph);
		$def[$def_n]  .= rrd::def("$name",           $RRDFILE[$ndx], $DS[$ndx], "MAX");
		$def[$def_n]  .= rrd::line1("$name", "#$color",$NAME[$ndx]."\t");
		$def[$def_n]  .= rrd::gprint("$name", array("LAST", "AVERAGE", "MAX"), "%6.2lf");
	}
}
##################################################################
# Graph ctxt (context switches/second)
if ( isset($ndx_ctxt) ) {
	$def_n++;
	$def[$def_n]=""; $opt[$def_n]=""; $ds_name[$def_n]="";
	$ds_name[$def_n] = "Context switches per second (all cores)";
	$opt[$def_n] = "--vertical-label \"context switches\" -l0  --title \"Context switches per second for $hostname / $servicedesc\" ";

	# Graph Context switches per second (total across all cpu cores)
	$def[$def_n]  .= rrd::def("ctxt",           $RRDFILE[$ndx_ctxt], $DS[$ndx_ctxt], "MAX");
	$def[$def_n]  .= rrd::area("ctxt",    "#c0c0ff");
	$def[$def_n]  .= rrd::line1("ctxt",   "#0000c0",$NAME[$ndx_ctxt]."\t\t");
	$def[$def_n]  .= rrd::gprint("ctxt", array("LAST", "AVERAGE", "MAX"), "%6.2lf");
}

##################################################################
# Graph processes (new processes/second)
if ( isset($ndx_processes) ) {
	$def_n++;
	$def[$def_n]=""; $opt[$def_n]=""; $ds_name[$def_n]="";
	$ds_name[$def_n] = "New processes per second (all cores)";
	$opt[$def_n] = "--vertical-label \"processes/s\" -l0  --title \"New processes per second for $hostname / $servicedesc\" ";

	# Graph Context switches per second (total across all cpu cores)
	$def[$def_n]  .= rrd::def("processes",           $RRDFILE[$ndx_processes], $DS[$ndx_processes], "MAX");
	$def[$def_n]  .= rrd::area("processes",    "#a0ffa0");
	$def[$def_n]  .= rrd::line1("processes",   "#00a000",$NAME[$ndx_processes]."\t\t");
	$def[$def_n]  .= rrd::gprint("processes", array("LAST", "AVERAGE", "MAX"), "%6.2lf");
}

#error_log($def[1]);
#error_log($def[2]);
#error_log($def[3]);
?>
