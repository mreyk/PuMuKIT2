/*! modernizr 3.3.1 (Custom Build) | MIT *
 * http://modernizr.com/download/?-inputtypes-touchevents-setclasses !*/
!function(e,t,n){function o(e,t){return typeof e===t}function s(){var e,t,n,s,a,i,l;for(var r in d)if(d.hasOwnProperty(r)){if(e=[],t=d[r],t.name&&(e.push(t.name.toLowerCase()),t.options&&t.options.aliases&&t.options.aliases.length))for(n=0;n<t.options.aliases.length;n++)e.push(t.options.aliases[n].toLowerCase());for(s=o(t.fn,"function")?t.fn():t.fn,a=0;a<e.length;a++)i=e[a],l=i.split("."),1===l.length?Modernizr[l[0]]=s:(!Modernizr[l[0]]||Modernizr[l[0]]instanceof Boolean||(Modernizr[l[0]]=new Boolean(Modernizr[l[0]])),Modernizr[l[0]][l[1]]=s),f.push((s?"":"no-")+l.join("-"))}}function a(e){var t=u.className,n=Modernizr._config.classPrefix||"";if(p&&(t=t.baseVal),Modernizr._config.enableJSClass){var o=new RegExp("(^|\\s)"+n+"no-js(\\s|$)");t=t.replace(o,"$1"+n+"js$2")}Modernizr._config.enableClasses&&(t+=" "+n+e.join(" "+n),p?u.className.baseVal=t:u.className=t)}function i(){return"function"!=typeof t.createElement?t.createElement(arguments[0]):p?t.createElementNS.call(t,"http://www.w3.org/2000/svg",arguments[0]):t.createElement.apply(t,arguments)}function l(){var e=t.body;return e||(e=i(p?"svg":"body"),e.fake=!0),e}function r(e,n,o,s){var a,r,f,d,c="modernizr",p=i("div"),h=l();if(parseInt(o,10))for(;o--;)f=i("div"),f.id=s?s[o]:c+(o+1),p.appendChild(f);return a=i("style"),a.type="text/css",a.id="s"+c,(h.fake?h:p).appendChild(a),h.appendChild(p),a.styleSheet?a.styleSheet.cssText=e:a.appendChild(t.createTextNode(e)),p.id=c,h.fake&&(h.style.background="",h.style.overflow="hidden",d=u.style.overflow,u.style.overflow="hidden",u.appendChild(h)),r=n(p,e),h.fake?(h.parentNode.removeChild(h),u.style.overflow=d,u.offsetHeight):p.parentNode.removeChild(p),!!r}var f=[],d=[],c={_version:"3.3.1",_config:{classPrefix:"",enableClasses:!0,enableJSClass:!0,usePrefixes:!0},_q:[],on:function(e,t){var n=this;setTimeout(function(){t(n[e])},0)},addTest:function(e,t,n){d.push({name:e,fn:t,options:n})},addAsyncTest:function(e){d.push({name:null,fn:e})}},Modernizr=function(){};Modernizr.prototype=c,Modernizr=new Modernizr;var u=t.documentElement,p="svg"===u.nodeName.toLowerCase(),h=c._config.usePrefixes?" -webkit- -moz- -o- -ms- ".split(" "):["",""];c._prefixes=h;var m=c.testStyles=r;Modernizr.addTest("touchevents",function(){var n;if("ontouchstart"in e||e.DocumentTouch&&t instanceof DocumentTouch)n=!0;else{var o=["@media (",h.join("touch-enabled),("),"heartz",")","{#modernizr{top:9px;position:absolute}}"].join("");m(o,function(e){n=9===e.offsetTop})}return n});var v=i("input"),y="search tel url email datetime date month week time datetime-local number range color".split(" "),g={};Modernizr.inputtypes=function(e){for(var o,s,a,i=e.length,l="1)",r=0;i>r;r++)v.setAttribute("type",o=e[r]),a="text"!==v.type&&"style"in v,a&&(v.value=l,v.style.cssText="position:absolute;visibility:hidden;",/^range$/.test(o)&&v.style.WebkitAppearance!==n?(u.appendChild(v),s=t.defaultView,a=s.getComputedStyle&&"textfield"!==s.getComputedStyle(v,null).WebkitAppearance&&0!==v.offsetHeight,u.removeChild(v)):/^(search|tel)$/.test(o)||(a=/^(url|email)$/.test(o)?v.checkValidity&&v.checkValidity()===!1:v.value!=l)),g[e[r]]=!!a;return g}(y),s(),a(f),delete c.addTest,delete c.addAsyncTest;for(var w=0;w<Modernizr._q.length;w++)Modernizr._q[w]();e.Modernizr=Modernizr}(window,document);