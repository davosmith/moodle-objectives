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

M.block_objectives = {panel: null};

M.block_objectives.show_fullscreen = function () {
    this.panel.cfg.setProperty('visible',true);
};

M.block_objectives.init_fullscreen = function(Y, icon, alt, startfull) {
    var el = document.getElementById('lesson_objectives_fullscreen_icon');
    if (el) {
	el.innerHTML = '<a href="#" onclick="M.block_objectives.show_fullscreen()"><img src="'+icon+'" alt="'+alt+'" /></a>';
    }
    var vis = false;
    if (startfull > 0) {
	vis = true;
    }

    this.panel = new YAHOO.widget.Panel('lesson_objectives_fullscreen', {
	width: "800px",
	fixedcenter: 'contained',
	constraintoviewport: true,
	underlay: "shadow",
	close: true,
	visible: vis,
	modal: true,
	zindex: 1000,
	draggable: false,
	effect: {effect:YAHOO.widget.ContainerEffect.FADE,duration:0.25}
    });

    this.panel.setHeader('Lesson objectives');
    var content = document.getElementById('lesson_objectives_fullscreen_text');
    this.panel.setBody(content.innerHTML);
    this.panel.render(document.body);
};
