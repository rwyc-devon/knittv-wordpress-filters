(function(){
	var forms=document.getElementsByClassName("knittv-filter");
	var baseurl=window.location.protocol + "//" + window.location.hostname;
	var getAttributes=function(form) {
		var elements=form.elements;
		var attributes={};
		for(var i=0; i<elements.length; i++) {
			var e=elements[i];
			if(e.name && elements.namedItem(e.name)) {
				attributes[e.name]=elements.namedItem(e.name).value;
			}
		}
		return attributes;
	};
	var getPrefix=function(form) {
		return form.getAttribute("data-prefix");
	};
	var submit=function(prefix, attributes) {
		var url=baseurl;
		if(prefix) {
			url+="/"+prefix;
		}
		var queryString="";
		for(i in attributes) {
			if(attributes[i].length>0) {
				if(queryString.length>0) {
					queryString+="&";
				}
				else {
					queryString+="?";
				}
				queryString += encodeURIComponent(i) + "=" + encodeURIComponent(attributes[i]);
			}
		}
		window.location.href=url+queryString;
	};
	var reset=function() {
		submit(getPrefix(this.parentNode), {s:this.parentNode.elements.namedItem("s").value});
	};
	var eventListener=function(event) {
		submit(getPrefix(this), getAttributes(this));
		event.preventDefault();
	};
	var focusFunc=function(event) {
		this.form.classList.add("focussed");
		event.stopPropagation();
	};
	for(i=0; i<forms.length; i++) {
		var form=forms[i];
		form.getElementsByClassName("reset")[0].addEventListener("click", reset);
		if(form.classList.contains("submitOnChange")) {
			form.addEventListener("change", eventListener);
		}
		form.addEventListener("submit", eventListener);
		if(form.classList.contains("popupFilters")) {
			var inputs=form.elements;
			for(i=0; i<inputs.length; i++) {
				var input=inputs[i];
				input.addEventListener("focus", focusFunc);
				input.addEventListener("click", focusFunc);
				input.addEventListener("blur", function() {
					window.setTimeout(0, function(){
						this.form.classList.remove("focussed");
					});
				});
				document.body.addEventListener("click", function() {
					form.classList.remove("focussed");
				});
			}
		}
	}
})();
