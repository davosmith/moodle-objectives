var lesson_objectives = new Object;

lesson_objectives.fshtml = null;
lesson_objectives.fsdiv = null;

lesson_objectives.show_fullscreen = function () {
    if (lesson_objectives.fsdiv) {
	lesson_objectives.fsdiv.setAttribute('style','');
    } else {
	var el = document.createElement('div');
	el.setAttribute('id','lesson_objectives_fullscreen_div');
	el.innerHTML = lesson_objectives.fshtml;
	el.addEventListener('click', function() { lesson_objectives.hide_fullscreen() });
	document.getElementById('page').appendChild(el);
	lesson_objectives.fsdiv = el;
    }
};

lesson_objectives.hide_fullscreen = function () {
    if (lesson_objectives.fsdiv) {
	lesson_objectives.fsdiv.setAttribute('style','display:hidden');
    }
};

lesson_objectives.init_fullscreen = function(icon, alt, fshtml) {
    var el = document.getElementById('lesson_objectives_fullscreen_icon');
    if (el) {
	//el.innerHTML = '<a href="#" onclick="lesson_objectives.show_fullscreen()"><img src="'+icon+'" alt="'+alt+'" /></a>';
    }
    lesson_objectives.fshtml = fshtml;
};