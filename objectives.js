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
	fixedcenter: true, 
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