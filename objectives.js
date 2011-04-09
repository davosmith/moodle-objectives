// This file is part of the Lesson Objectives plugin for Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

var lesson_objectives = {panel: null, startfull: 0};

lesson_objectives.show_fullscreen = function () {
    this.panel.cfg.setProperty('visible',true);
};

lesson_objectives.init_fullscreen = function(icon, alt, startfull) {
    var el = document.getElementById('lesson_objectives_fullscreen_icon');
    if (el) {
	el.innerHTML = '<a href="#" onclick="lesson_objectives.show_fullscreen()"><img src="'+icon+'" alt="'+alt+'" /></a>';
    }
    this.startfull = startfull;
};

YAHOO.util.Event.onDOMReady(function() {
    document.body.className += ' yui-skin-sam';
    var vis = false;
    if (lesson_objectives.startfull > 0) {
	vis=true;
    }

    lesson_objectives.panel = new YAHOO.widget.Panel('lesson_objectives_fullscreen', {
	width: "800px",
	fixedcenter: 'contained',
	constraintoviewport: true,
	underlay: "shadow",
	close: true,
	visible: vis,
	modal: true,
	zindex: 10,
	draggable: false,
	effect: {effect:YAHOO.widget.ContainerEffect.FADE,duration:0.25}
    });

    lesson_objectives.panel.setHeader('Lesson objectives');
    var content = document.getElementById('lesson_objectives_fullscreen_text');
    lesson_objectives.panel.setBody(content.innerHTML);
    lesson_objectives.panel.render(document.body);
});