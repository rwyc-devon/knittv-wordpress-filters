(function(){
	var listener=function(e) {
		if(!this.value) {
			this.parentNode.removeChild(this);
		}
	};
	var appendClone=function(select) {
			var c=select.cloneNode(true);
			c.value="";
			console.log(c.name);
			var matches=c.name.match('^(.+)\\[tax(\\d\\d)\\]$');
			console.log(matches);
			var n=(("000"+(parseInt(matches[2], 10)+1))).substr(-2);
			c.name=matches[1]+"[tax"+(n)+"]";
			c.addEventListener("change", lastListener);
			return c;
	};
	var lastListener=function(e) {
		if(this.value) {
			this.removeEventListener("change", lastListener);
			this.addEventListener("change", listener);
			this.parentNode.appendChild(appendClone(this));
		}
	};
	var addListeners=function() {
		var widgets=document.querySelectorAll("[id*=knittvfilter] .filterchooser");
		for(i=0; i<widgets.length; i++) {
			var selects=widgets[i].getElementsByTagName("select");
			for(ii=0; ii<selects.length-1; ii++) {
				selects[ii].removeEventListener("change", listener);
				selects[ii].addEventListener("change", listener);
			}
			selects[selects.length-1].removeEventListener("change", lastListener);
			selects[selects.length-1].addEventListener("change", lastListener);
		}
	};
	setInterval(addListeners, 500); //TODO: call this only when the form reloads.
})();
