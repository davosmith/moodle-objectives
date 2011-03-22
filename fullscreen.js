var lesson_objectives = new Object;

lesson_objectives.add_fs_objective = function ($complete, $text) {

};

lesson_objectives.show_fullscreen = function () {
    alert('fullscreen');
};

lesson_objectives.init_fullscreen = function(icon, alt) {
    var el = document.getElementById('lesson_objectives_fullscreen');
    if (el) {
	el.innerHTML = '<a href="#" onclick="lesson_objectives.show_fullscreen()"><img src="'+icon+'" alt="'+alt+'" /></a>';
    }
};