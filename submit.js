(function(){
	var forms=document.getElementsByClassName("knittv-filter");
	for(i=0; i<forms.length; i++) {
		var form=forms[i];
		var eventListener=function(event) {
			if(!(event.type=="change" && event.target.name=="s")) {
				var elements=this.elements;
				var results={};
				for(var i=0; i<elements.length; i++) {
					var e=elements[i];
					if(e.name && elements.namedItem(e.name)) {
						results[e.name]=elements.namedItem(e.name).value;
					}
				}
				var query="";
				for(i in results) {
					if(results[i].length>0) {
						if(query.length>0) {
							query+="&";
						}
						query+=encodeURIComponent(i)+"="+encodeURIComponent(results[i]);
					}
				}
				var baseurl=window.location.protocol + "//" + window.location.hostname;
				if(query) {
					window.location.href=(baseurl+"?"+query);
				}
				else {
					window.location.href=(baseurl);
				}
				event.preventDefault();
			}
		};
		if(form.classList.contains("submitOnChange")) {
			form.addEventListener("change", eventListener);
		}
		form.addEventListener("submit", eventListener);
		if(form.classList.contains("popupFilters")) {
			var inputs=form.elements;
			for(i=0; i<inputs.length; i++) {
				var input=inputs[i];
				var focusFunc= function(event) {
					console.log("focus");
					this.form.classList.add("focussed");
					event.stopPropagation();
				};
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
