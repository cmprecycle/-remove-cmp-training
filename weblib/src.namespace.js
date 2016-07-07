//var namespace=function(s,r,c,i,k){r=r||/^u/.test(typeof window)?global:window;c=s.split('.');for(i=0;i<c.length;i++){k=c[i];r[k]||(r[k]={});r=r[k];}return r};
/^u/.test(typeof namespace)&&(namespace=function(s,r,c,i,k){r=r||/^u/.test(typeof window)?global:window;c=s.split('.');for(i=0;i<c.length;i++){k=c[i];r[k]||(r[k]={});r=r[k];}return r});
/^u/.test(typeof module)||function(m){m.exports=namespace}(module);
/**
Usage:

web:
namespace('x.y').z=function(s){alert(s)};
x.y.z('ok');
namespace('x.y').t=function(s){return 99};
alert(o2s(x.y));

nodejs:
namespace=require('./namespace.js');

namespace('x.y').t=function(s){return 99};
console.log(o2s(x.y));
*/
