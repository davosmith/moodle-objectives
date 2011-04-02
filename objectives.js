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
	fixedcenter: true, 
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
