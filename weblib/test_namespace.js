var ns=require('./src.namespace.js');
console.log(ns);
/^u/.test(typeof namespace)||console.log(namespace);
namespace=require('./src.namespace.js');
console.log(namespace);

namespace('x.y').z=99;
console.log(x);


