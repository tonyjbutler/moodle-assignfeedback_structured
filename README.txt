This file is part of Moodle - http://moodle.org/

Moodle is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Moodle is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

package   assignfeedback_structured
copyright 2017 Lancaster University (http://www.lancaster.ac.uk/)
license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
author    Tony Butler <a.butler4@lancaster.ac.uk>


Structured feedback assignment subplugin for Moodle
===================================================

The structured feedback assignment subplugin enables teachers to define
any number of criteria on which to provide individual feedback comments
for a student's assignment submission.

The configured 'criteria set' can then be saved for easy reuse in other
assignments. Given the appropriate permissions the teacher can also
choose to share their saved criteria sets with other teachers within
the same Moodle site.


Changelog
---------

2021-09-27  v3.9.1
  * Add Moodle plugin CI github action tests
  * Address issues identified by code prechecker

2021-09-24  v3.9.0
  * Add support for files in feedback comments
  * Fix bug preventing criteria sets being saved
  * Fix lost comment updates on grading validation failure
  * Include criterion name in field description
  * Update additional criteria fields template

2019-06-11  v3.5.1
  * Bootstrap updates + accessibility adjustments
  * Add support for pushing feedback to gradebook

2018-09-28  v3.5.0
  * Enable criteria details to be styled easily
  * Hide user summary if no criteria are defined
  * Add privacy provider for GDPR support

2017-06-21  v3.3.1
  * Use placeholder for quickgrading description
  * Don't empty config if it's already empty
  * Replace public (reserved in JS) with shared
  * Address remaining code prechecker issues

2017-06-14  v3.3.0
  * Initial stable release


Installation
------------

Installing from the Git repository (recommended if you installed Moodle
from Git):

Follow the instructions at
http://docs.moodle.org/39/en/Git_for_Administrators#Installing_a_contributed_extension_from_its_Git_repository,
e.g. for the Moodle 3.9.x code:

$ cd /path/to/your/moodle/
$ cd mod/assign/feedback/
$ git clone https://github.com/tonyjbutler/moodle-assignfeedback_structured.git structured
$ cd structured/
$ git checkout -b MOODLE_39_STABLE origin/MOODLE_39_STABLE
$ git branch -d master
$ cd /path/to/your/moodle/
$ echo /mod/assign/feedback/structured/ >> .git/info/exclude


Installing from a zip archive downloaded from
https://moodle.org/plugins/pluginversions.php?plugin=assignfeedback_structured:

1. Download and unzip the appropriate release for your version of
   Moodle.
2. Copy the extracted "structured" folder into your
   "/mod/assign/feedback/" subdirectory.

Whichever method you use to get the plugin code in place, the final
step is to visit your Site Administration > Notifications page in a
browser to invoke the installation script and make the necessary
database changes.


Updating Moodle
---------------

If you installed Moodle and the Structured feedback plugin from Git you
can run the following commands to update both (see
http://docs.moodle.org/39/en/Git_for_Administrators#Installing_a_contributed_extension_from_its_Git_repository):

$ cd /path/to/your/moodle/
$ git pull
$ cd mod/assign/feedback/structured/
$ git pull


If you installed from a zip archive you will need to repeat the
installation procedure using the appropriate zip file downloaded from
https://moodle.org/plugins/pluginversions.php?plugin=assignfeedback_structured
for your new Moodle version.
