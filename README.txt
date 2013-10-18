Name

check_cpu.py - Usage and IO Wait

Description

Monitor total CPU usage and CPU IO Wait for any single CPU core, and overall (average of all CPU cores)


Detailed Description

This plugin will generate an alert if
* The overall CPU usage exceeds a given threshold
* The overall CPU IO Wait exceeds a given threshold
* The CPU usage of any single CPU core exceeds a threshold
* The CPU IO Wait of any single CPU core exceeds a threshold

This plugin also generates performance data suitable for PNP4Nagios, and a suitable PNP4Nagios template is supplied.
The template provides a neater presentation than the default.

Note that this plugin measures CPU usage by reading /proc/stat twice over a time interval (default is 1 second).
As such:
* It is specific to Linux, and probably won't work on other systems
* It does NOT rely on the python library psutil, and hence is backwards compatible
  with older Linux systems which do have the psutil library.

This plugin is a fork of check_cpu.py by Kirk Hammond, and was written to cater for older Linux distributions which do
not have a the library psutil available. It also adds the feature of monitoring IO Wait explicitly.

