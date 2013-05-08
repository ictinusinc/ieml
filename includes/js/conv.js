var alToVowel = {
    'U:U:E:': 'wo', 'U:A:E:': 'wa', 'U:S:E:': 'y', 'U:B:E:': 'o', 'U:T:E:': 'e',
    'A:U:E:': 'wu', 'A:A:E:': 'we', 'A:S:E:': 'u', 'A:B:E:': 'a', 'A:T:E:': 'i',
    'S:U:E:': 'j', 'S:A:E:': 'g', 'S:S:E:': 's', 'S:B:E:': 'b', 'S:T:E:': 't',
    'B:U:E:': 'h', 'B:A:E:': 'c', 'B:S:E:': 'k', 'B:B:E:': 'm','B:T:E:': 'n',
    'T:U:E:': 'p', 'T:A:E:': 'x', 'T:S:E:': 'd', 'T:B:E:': 'f', 'T:T:E:': 'l'
};

var vowelToAl = {};
for (var i in alToVowel)
	vowelToAl[alToVowel[i]] = i;
	
var shortToAl = {
    'O': 'U:+A:',
    'M': 'S:+B:+T:',
    'F': 'U:+A:+S:+B:+T:', //'F': 'O:+M:',
    'I': 'E:+U:+A:+S:+B:+T:' //'I': 'E:+F:'
};
  
  var lvlToSym = [':', '.', '-', "'", ',', '_', ';'];
  var symToLvl = {};
for (var i in lvlToSym)
	symToLvl[lvlToSym[i]] = i;

//var strReg = {ATOM: "(U|A|S|B|T|E)", L0: ":", L1: "\\.", L2: "-", L3: "'", L4: ",", L5: "_", L6: ";", END: "\\*\\*", START: "\\*"};
var strReg = {
	ATOM: "(U|A|S|B|T|E)",
	LEVELS: "(:|\\.|-|'|,|_|;)",
	END: "\\*\\*",
	START: "\\*",
	SHORT: "(I|O|M|F)",
	PLUS: "\\+",
	LPAREN: '\\(',
	RPAREN: '\\)',
	VOWEL: '(' + Object.keys(vowelToAl).join('|') + ')'
};

var regMap = {};
for (var i in strReg)
	regMap[i] = new RegExp('^' + strReg[i]);

function tokenize(str) {
	var ret = [];
    for (var i=0; i<str.length; i++) {
    	for (var j in regMap) {
        	var mat = str.substr(i).match(regMap[j]);
        	if (mat) {
        		mat = mat[0];
        		ret.push({'type':j, 'str':mat});
        		i += mat.length - 1;
        		break;
        	}
    	}
    }
    return ret;
}

function tokensToStr(tok) {
    var ret = '';
    for (var i in tok)
        ret += tok[i]['str'];
    
    return ret;
}

function objArrToStr(arr) {
	var ret = '';

	for (var i=0; i<arr.length; i++) {
		if (arr[i]['type'] == 'levelArr')
			ret += objArrToStr(arr[i]['data']) + arr[i]['data2'];
		else
			ret += arr[i]['data'].getComplete() + (i+1==arr.length?'':'.');
	}

	return ret;
}

function objArrToFlatStr(arr) {
	var ret = '';

	for (var i=0; i<arr.length; i++) {
		if (arr[i]['type'] == 'levelArr')
			ret += objArrToFlatStr(arr[i]['data']);
		else
			ret += arr[i]['data'].getCompleteFlat();
	}

	return ret;
}
    
function triple() {
    this.els = [];
    this.length = 0;
    
    this.push = function(n) {
        this.els.push(n);
        this.length++;
        
        return this;
    };
    this.pop = function() {
        var ret = this.els.pop();
        this.length--;
        return ret;
    };
    this.peek = function() {
        return this.els[this.els.length-1];
    };
    this.size = function() {
        return this.els.length;
    };
    
    this.set = function(n, el) {
    	if (typeof this.els[n] === 'undefined') return;
        this.els[n] = el;
        
        return this;
    };
    this.getFlat = function(n) {
    	var ret = this.els[n];
        return ret?ret:'E';
    };
    this.get = function(n) {
    	return this.getFlat(n)+':';
    };
    this.getComplete = function() {
        return ''+this.get(0)+this.get(1)+this.get(2);
    };
    this.getCompleteFlat = function() {
        return ''+this.getFlat(0)+this.getFlat(1)+this.getFlat(2);
    };
    
	for (var i=0; i<arguments.length; i++)
		this.push(arguments[i]);
}
triple.combine = function(a,b) {
    var ret = new triple();
    
    for (var i=0; i<a.els.length; i++)
    	ret.push(a.els[i]);
    for (var i=0; i<b.els.length; i++)
    	ret.push(b.els[i]);
    
    return ret;
};

function checkPos(n, arr) {
    if (n<0 || n >= arr.length) return;
    return arr[n];
}

function preprocess(preTok) {
	var tok = [];
	//preprocess, only unrolls short forms and vowels
	for (var i=0; i<preTok.length; i++) {
	  switch (preTok[i]['type']) {
		  case 'SHORT':
			tok = tok.concat(preprocess(tokenize('(' + shortToAl[preTok[i]['str']] + ')')));
			i++
			break;
		  case 'VOWEL':
			tok = tok.concat(preprocess(tokenize(vowelToAl[preTok[i]['str']] + '.')));
			i++;
			break;
		  case 'LEVELS':
			if (preTok[i]['str'] != ':')
				tok.push(preTok[i]);
			break;
		  default:
			tok.push(preTok[i]);
	  }
	}
	
	return tok;
}

function mulArr(arr, i) {
	if (typeof i == 'undefined') i = 0;
	
	var out = [], next;
	
	if (arr[i]['type'] && arr[i]['type'] == 'levelArr') {
		out.push({'type':'levelArr', 'data':mulArr(arr[i]['data'], 0), 'data2':arr[i]['data2']});
		if (i+1 < arr.length) {
			next = mulArr(arr, i+1);
			out = out.concat(next);
		}
	} else {
		if (i + 1 == arr.length) return arr[i];
		else if (i + 1 > arr.length) return undefined;
		
		next = mulArr(arr, i+1);
		for (var j=0; j<arr[i].length; j++) {
			for (var k=0; k<next.length; k++) {
				out.push({'type':'triple', 'data':triple.combine(arr[i][j]['data'], next[k]['data'])});
			}
		}
	}
	
	return out;
}

function unrollAST(ast) {
	var out = [];
	
	if (ast instanceof Array) {
    	for (var i=0; i<ast.length; i++) {
        	if (ast[i]['type'] === 'triple') {
        		out.push([ast[i]]);
        	} else if (ast[i]['type'] === 'tree') {
				out.push(unrollAST(ast[i]));
        	} else if (ast[i]['type'] === 'level') {
        		out.push({'type':'levelArr', 'data': unrollAST(ast[i]), 'data2':ast[i]['data2']});
			}
    	}
	} else if (ast['type'] == 'level') {
		for (var i=0; i<ast['data'].length; i++) {
        	if (ast['data'][i]['type'] === 'triple')
        		out.push([ast['data'][i]]);
        	else if (ast['data'][i]['type'] === 'tree')
        		out.push(unrollAST(ast['data'][i]));
        	else if (ast['data'][i]['type'] === 'level')
        		out.push({'type':'levelArr', 'data': unrollAST(ast['data'][i]), 'data2':ast['data'][i]['data2']});
		}
	} else {
    	var d = [ast['data'], ast['data2']], op = ast['oper'];
    	
    	if (op == '+')
        	for (var j = 0; j<d.length; j++) {
	        	if (d[j]['type'] == 'triple')
	        		out.push(d[j]);
	        	else if (d[j]['type'] == 'tree')
	        		out = out.concat(unrollAST(d[j]));
				else if (d[j]['type'] === 'level')
					out.push({'type':'levelArr', 'data': unrollAST(d[j]), 'data2':d[j]['data2']});
        	}
	}
	
	return out;
}

function STARtoAL(str) {
	var outputQ = [], opStack = [], tok = [], preTok = tokenize(str), unrolled;

	tok = preprocess(preTok);
	
	console.log(tokensToStr(tok));

	//shunting-yard algorithm to generate AST(outputQ)
	tstream: for (var i in tok) {
		switch (tok[i]['type']) {
			case 'START':
				//whoopti-doo, we have begun!
				break;
			case 'ATOM':
				var topOut = checkPos(outputQ.length-1, outputQ);
				if (topOut && topOut['type'] == 'triple' && topOut['data'].length <3 && checkPos(i-1, tok)['type'] == 'ATOM') {
					topOut['data'].push(tok[i]['str']);
				} else {
					outputQ.push({'type': 'triple', 'data': new triple(tok[i]['str'])});
				}
			break;
			case 'LEVELS':
				//assume operator stack is empty, as it should be
				var temp = [];
				while (opStack.length>0) {
					var op = opStack.pop(), n1 = outputQ.pop(), n2 = outputQ.pop();
					outputQ.push({'type': 'tree', 'data': n2, 'data2': n1, 'oper':op});
				}
				while (outputQ.length > 0) {
					var top = checkPos(outputQ.length-1, outputQ);
					if (top && top['type'] == 'level' && symToLvl[top['data2']] >= symToLvl[tok[i]['str']])
						break;
						
					temp.push(outputQ.pop());
				}
				
				outputQ.push({'type':'level', 'data':temp.reverse(), 'data2':tok[i]['str']});
				break;
			case 'PLUS':
				opStack.push(tok[i]['str']);
				break;
			case 'LPAREN':
				opStack.push(tok[i]['str']);
				break;
			case 'RPAREN':
				while (opStack.length>0 && opStack[opStack.length-1] != '(') {
					var op = opStack.pop(), n1 = outputQ.pop(), n2 = outputQ.pop();
					outputQ.push({'type': 'tree', 'data': n2, 'data2': n1, 'oper':op});
				}
				if (opStack.length == 0) return 'Mismatched parens.';
				opStack.pop();
				break;
			case 'END':
				//aaand we're done
				break tstream;
			default:
				console.log('Invalid token type: "' + tok[i]['type'] +'"');
		}
	}
	while (opStack.length>0) {
		var op = opStack.pop(), n1 = outputQ.pop(), n2 = outputQ.pop();
		outputQ.push({'type': 'tree', 'data': n2, 'data2': n1, 'oper':op});
	}
	
	console.log(outputQ, opStack);

	unrolled = unrollAST(outputQ);
	
	console.log(unrolled);
	
	return mulArr(unrolled);
}

/*
	objArrToStr converts the internal object representation of the output of STARtoAL to a human readable string representation
	
	example:
	objArrToStr(STARtoAL("*d.o.-o.o.-s.y.-'**")) == "T:S:E:.U:B:E:.-U:B:E:.U:B:E:.-S:S:E:.U:S:E:.-'" (accounting);
	
	objArrToStr(STARtoAL("*f.o.-x.x.-'**")) == "T:B:E:.U:B:E:.-T:A:E:.T:A:E:.-'" (active bimolecule);
*/