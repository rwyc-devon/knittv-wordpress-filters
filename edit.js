(function(){
	var focusListener=function(e) {
		this.oldValue=this.value;
	}
	var genericListener=function(e) {
		var v=this.value;
		this.value="";
		if(getValues(this.parentNode).indexOf(v)>=0) {
			this.value=this.oldValue;
		}
		else {
			this.value=v;
			this.oldValue=this.value;
		}
	}
	var listener=function(e) {
		if(!this.value) {
			this.parentNode.removeChild(this);
		}
	};
	var getValues=function(widget) {
		var selects=widget.getElementsByTagName("select");
		var values=[];
		for(i=0; i<selects.length; i++) {
			if(selects[i].value) {
				values.push(selects[i].value);
			}
		}
		return values;
	}
	var appendClone=function(select) {
		//make clone
		var c=select.cloneNode(true);
		//reset value
		c.value="";
		//make a new name attribute (most fragile part of this whole thing!)
		var matches=c.name.match('^(.+)\\[tax(\\d\\d)\\]$');
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
			var widget=widgets[i];
			var selects=widgets[i].getElementsByTagName("select");
			for(ii=0; ii<selects.length; ii++) {
				selects[ii].removeEventListener("focus", focusListener);
				selects[ii].addEventListener("focus", focusListener);
				selects[ii].removeEventListener("change", genericListener);
				selects[ii].addEventListener("change", genericListener);
			}
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
