Math.LN3 = Math.log(3);

function checkPos(n, arr) {
    if (n >= 0 && n < arr.length)
        return arr[n];
}

function array_map(arr, callback) {
    var ret = [];
    for (var i in arr)
        ret[i] = callback(arr[i]);
    return ret;
}

function array_foldr(arr, callback, def) {
    var ret = typeof def == 'undefined' ? 0 : def;

    for (var i in arr)
        ret = callback(ret, arr[i]);

    return ret;
}

function rpad(str, n, chr) {
    if (str.length >= n) return str;
    var ret = str;
    for (var i=0; i<n-str.length; i++)
        ret+=chr;
    return ret;
}

function ASTNode() {
    this._type = ''; //leaf (value) or fcn/op (internal)
    this._children = []; //operators will usually have 2, functions possibly more
    this._val = ''; //element value
    this.length = 0; //initial child count
    
    this.type = function() {
        if (arguments.length == 0)
            return this._type;
        else {
            this._type = arguments[0];
            return this;
        }
    };
    this.val = function() {
        if (arguments.length == 0) {
            return this._val;
        } else {
            this._val = arguments[0];
            return this;
        }
    };
    this.children = function() {
        if (arguments.length == 0)
            return this._children;
        else
            return this._children[arguments[0]];
    };
    this.push = function(child) {
        if (child instanceof Array) {
            for (var i = 0; i < child.length; i++)
                this._children.push(child[i]);
        } else {
            this._children.push(child);
        }
        this._recheckLen();
        
        return this;
    };
    this.setChildren = function(n, val) {
        if (typeof val == 'undefined' && n instanceof Array) {
            this._children = n;
            this._recheckLen();
        } else
            this._children[n] = val;
        return this;
    };
    this.concat = function(b) {
        if (b && b instanceof ASTNode) {
            for (var i = 0; i < b.length; i++)
                this.push(b.children(i));
            this._recheckLen();
        }
        
        return this;
    };
    this._recheckLen = function() {
        this.length = this._children.length;
    };
    this.toString = function() {
        var chstr = '';
        if (this._type == 'internal') {
            for (var i=0; i<this.length; i++)
                chstr += (i==0?'':', ') + this._children[i];
            chstr = 'children: {length: '+this.length+', con: ['+chstr+']}';
        }
            
        return 'ASTNode{type: "' + this._type + '", val: "'+this._val+'"'+(chstr==''?'':', '+chstr)+'}';
    };
    
    var n = arguments.length;
    if (n == 1) {
        this._type = arguments[0];
    } else if (n == 2) {
        this._type = arguments[0];
        this._val = arguments[1];
    } else if (n == 3) {
        this._type = arguments[0];
        this._val = arguments[1];
        if (arguments[2] instanceof Array) {
            this._children = arguments[2];
        } else {
            this._children.push(arguments[2]);
        }
        this._recheckLen();
    } else if (n > 3) {
        this._type = arguments[0];
        this._val = arguments[1];
        for (var i = 2; i < n; i++)
            this._children.push(arguments[i]);
        this._recheckLen();
    }
    
    return this;
}

function Triple() {
    this._els = [];
    this._level = 0;
    this.length = 0;
    
    this.push = function(n) {
        this._els.push(n);
        this._recheckLen();
        
        return this;
    };
    this.pop = function() {
        var ret = this._els.pop();
        this._recheckLen();
        return ret;
    };
    this.peek = function() {
        return this._els[this._els.length - 1];
    };
    this.size = function() {
        return this._els.length;
    };
    
    this.set = function(n, el) {
        if (typeof this._els[n] !== 'undefined')
            this._els[n] = el;
        
        return this;
    };
    this.level = function(s) {
        if (typeof s == 'undefined')
            return this._level;
        else
            this._level = s;
        return this;
    };
    this.get = function(n) {
        var ret = this._els[n];
        return ret ? ret : 'E';
    };
    this.getStr = function(n) {
        var ret = this._els[n];
        return ret ? ret.toString() : 'E';
    };
    this.getComplete = function() {
        if (this._level == 0) {
            return this.get(0);
        }
        var ret = [];
        for (var i=0; i<Math.pow(3, this._level); i++)
            ret.push(this.get(i));
        return ret;
    };
    this.getCompleteStr = function() {
        return array_foldr(this.getComplete(), function(a,b) { return a+b; }, '');
    };
    this.getValue = function() {
        return array_foldr(this.getComplete(), function(a,b) { return a + STARtoAL.primitiveVal[b]; }, 0);
    };
    this._recheckLen = function() {
        this.length = this._els.length;
        this._recheckLevel();
    };
    this._recheckLevel = function() {
        if (this._els.length > Math.pow(3, this._level))
            this._level = Math.ceil(Math.log(this._els.length)/Math.LN3);
    };
    this.toString = function() {
        return 'Triple{level: '+this._level+', str: "'+this.getCompleteStr()+'"}';
    };
    
    var n = arguments.length;
    if (n == 1) {
        this._level = arguments[0];
    } else if (n == 2) {
        this._level = arguments[0];
        if (arguments[1] instanceof Array) {
            this._els = arguments[1];
        } else {
            this._els.push(arguments[1]);
        }
        this._recheckLen();
    } else if (n > 2) {
        this._level = arguments[0];
        for (var i = 1; i < n; i++)
            this._els.push(arguments[i]);
        this._recheckLen();
    }
}

Triple.merge = function(a, b) {
    var ret;
    var ac, bc;
    if (a.level() > b.level()) {
        ac = a._els;
        bc = b.getComplete();
        ret = new Triple(a.level())
    } else if (a.level() < b.level()) {
        ac = a.getComplete();
        bc = b._els;
        ret = new Triple(b.level())
    } else {
        ac = a.getComplete();
        bc = b.getComplete();
        ret = new Triple(a.level()+1)
    }
    
    for (var i = 0; i < ac.length; i++)
        ret.push(ac[i]);
    for (var i = 0; i < bc.length; i++)
        ret.push(bc[i]);
    
    return ret;
};
Triple.checkMerge = function(a, b) {
    return '_els' in a && '_els' in b;
};

function Token() {
    this._type;
    this._val;
    
    this.type = function() {
        if (arguments.length == 0)
            return this._type;
        else
            this._type = arguments[0];
        return this;
    };
    this.val = function() {
        if (arguments.length == 0)
            return this._val;
        else
            this._val = arguments[0];
        return this;
    };
    this.toString = function() {
        return 'Token{type: "' + this._type + '", val: "'+this._val+'"}';
    };
    
    var n = arguments.length;
    if (n == 1) {
        this._type = arguments[0];
    } else if (n == 2) {
        this._type = arguments[0];
        this._val = arguments[1];
    }
    
    return this;
}

function STARtoAL() {
    
    return this;
}

STARtoAL.alToVowel = {
    'UUE': 'wo','UAE': 'wa','USE': 'y','UBE': 'o','UTE': 'e',
    'AUE': 'wu','AAE': 'we','ASE': 'u','ABE': 'a','ATE': 'i',
    'SUE': 'j','SAE': 'g','SSE': 's','SBE': 'b','STE': 't',
    'BUE': 'h','BAE': 'c','BSE': 'k','BBE': 'm','BTE': 'n',
    'TUE': 'p','TAE': 'x','TSE': 'd','TBE': 'f','TTE': 'l'
};
STARtoAL.vowelToAl = {
    'wo': 'UUE','wa': 'UAE','y': 'USE','o': 'UBE','e': 'UTE',
    'wu': 'AUE','we': 'AAE','u': 'ASE','a': 'ABE','i': 'ATE',
    'j': 'SUE','g': 'SAE','s': 'SSE','b': 'SBE','t': 'STE',
    'h': 'BUE','c': 'BAE','k': 'BSE','m': 'BBE','n': 'BTE',
    'p': 'TUE','x': 'TAE','d': 'TSE','f': 'TBE','l': 'TTE'
};
STARtoAL.shortToAl = {
    'O': 'U+A',
    'M': 'S+B+T',
    'F': 'U+A+S+B+T', //'F': 'O:+M:',
    'I': 'E+U+A+S+B+T' //'I': 'E:+F:'
};
STARtoAL.lvlToSym = [':', '.', '-', "'", ',', '_', ';'];
STARtoAL.symToLvl = {':': 0,'.': 1,'-': 2,"'": 3,',': 4,'_': 5,';': 6};
STARtoAL.primitiveVal = {'E': 1,'U': 2,'A': 4,'S': 8,'B': 16,'T': 32};
STARtoAL.strReg = {
    ATOM: '(' + Object.keys(STARtoAL.primitiveVal).join('|') + ')',
    LEVELS: "(:|\\.|-|'|,|_|;)",
    END: "\\*\\*",
    START: "\\*",
    SHORT: "(I|O|M|F)",
    PLUS: "\\+",
    MUL: "\\*",
    LPAREN: '\\(',
    RPAREN: '\\)',
    FORSLASH: '/',
    VOWEL: '(' + Object.keys(STARtoAL.vowelToAl).join('|') + ')'
};
STARtoAL.regMap = {};
for (var i in STARtoAL.strReg)
    STARtoAL.regMap[i] = new RegExp('^' + STARtoAL.strReg[i]);

/**
 * Returns a human readable string from an array of tokens
 */
STARtoAL.tokensToStr = function(tok) {
    var ret = '';
    for (var i in tok)
        ret += tok[i].val();
    
    return ret;
}

/**
 * Returns a human readable, unambiguous string representation of the internal object structure
 */
STARtoAL.astObjectStr = function(tok) {
    if (!tok)
        return '';
    ret = '';
    if ('type' in tok.val()) {
        if (tok.val().type() == 'LEVELS') {
            for (var i = 0; i < tok.length; i++)
                ret += STARtoAL.astObjectStr(tok.children(i));
            ret += tok.val().val();
        } else if (tok.val().type() == 'PLUS') {
            ret += '(';
            for (var i = 0; i < tok.length; i++)
                ret += (i == 0 ? '' : ' + ') + STARtoAL.astObjectStr(tok.children(i));
            ret += ')';
        } else if (tok.val().type() == 'MUL') {
            ret += '(';
            for (var i = 0; i < tok.length; i++)
                ret += (i == 0 ? '' : ' * ') + STARtoAL.astObjectStr(tok.children(i));
            ret += ')';
        }
    } else if (tok.type() == 'value') {
        ret += tok.val().getCompleteStr();
    }
    
    return ret;
}

STARtoAL.astToStr = function(tok) {
    if (!tok) return '';
    var ret = '';
    //console.log(tok.val());
    if (tok.type() == 'internal') {
        if (tok.val().type() == 'LEVELS') {
            for (var i = 0; i < tok.length; i++) {
                ret += (i==0?'':', ') + STARtoAL.astToStr(tok.children(i));
            }
        } else if (tok.val().type() == 'PLUS') {
            ret += '{';
            for (var i = 0; i < tok.length; i++) {
                ret += (i==0?'':', ') + STARtoAL.astToStr(tok.children(i));
            }
            ret += '}'
        } else {
            for (var i = 0; i < tok.length; i++) {
                ret += STARtoAL.astToStr(tok.children(i));
            }
        }
    } else {
        ret += tok.val().getCompleteStr();
    }
    
    return ret;
}

/**
 * Converts a string into an array of tokens.
 */
STARtoAL._tokenize = function(str) {
    var ret = [];
    for (var i = 0; i < str.length; i++) {
        for (var j in STARtoAL.regMap) {
            var mat = str.substr(i).match(STARtoAL.regMap[j]);
            if (mat) {
                mat = mat[0];
                ret.push(new Token(j, mat));
                i += mat.length - 1;
                break;
            }
        }
    }
    return ret;
};

/**
 * Preprocesses an array of tokens for use in the shunting yard algorithm below.
 */
STARtoAL._preprocess = function(tokens) {
    var out = [];
    //preprocess, only unrolls short forms and vowels
    for (var i = 0; i < tokens.length; i++) {
        switch (tokens[i].type()) {
            case 'SHORT':
                out = out.concat(STARtoAL._tokenize('(' + STARtoAL.shortToAl[tokens[i].val()] + ')'));
                break;
            case 'VOWEL':
                out = out.concat(STARtoAL._tokenize(STARtoAL.vowelToAl[tokens[i].val()]));
                break;
            case 'LEVELS':
                if (tokens[i].val() != ':')
                    out.push(tokens[i]);
                break;
            default:
                out.push(tokens[i]);
        }
    }
    
    return out;
};

/**
 * removes all instances of implicit multiplication by inserting a multiplication token where necessary
 */
STARtoAL._removeImplicitMul = function(tokens) {
    var ret = [];
    for (var i=0; i<tokens.length; i++) {
        switch(tokens[i].type()) {
            case 'ATOM':
                var prevTok = checkPos(i - 1, tokens);
                if (prevTok && prevTok.type() == 'RPAREN' || prevTok.type() == 'LEVELS')
                    ret.push(new Token('MUL', '*'));
                break;
            case 'LPAREN':
                var prevTok = checkPos(i - 1, tokens);
                if (prevTok && prevTok.type() == 'RPAREN' || prevTok.type() == 'ATOM' || prevTok.type() == 'LEVELS')
                    ret.push(new Token('MUL', '*'));
                break;
            default: break;
        }
        ret.push(tokens[i]);
    }
    return ret;
};

/**
 * convert stream of preprocessed tokens to an abstact syntax tree
 */
STARtoAL.SYA = function(procTokens) {
    var opers = [], trees = [], glob_trees = [];
    
    tstream: 
    for (var i = 0; i < procTokens.length; i++) {
        switch (procTokens[i].type()) {
            case 'START':
            	//we've started, nothing to do here
                break;
            case 'ATOM':
                var prevTok = checkPos(i - 1, procTokens), top = checkPos(trees.length - 1, trees);
                if (prevTok && prevTok.type() == 'ATOM' && top && top.type() == 'value' && top.val().length < 3)
                    top.val().push(procTokens[i].val());
                else {
                    trees.push(new ASTNode('value', new Triple(0, procTokens[i].val())));
                    
                    if (prevTok && (prevTok.type() == 'RPAREN' || prevTok.type() == 'ATOM')) {
                        trees.push(new ASTNode('internal', new Token('MUL', '*'), [trees.pop(), trees.pop()].reverse()));
                    }
                }
                break;
            case 'LEVELS':
                while (opers.length > 0 && trees.length > 1) {
                    var top = checkPos(trees.length-1, trees), top2nd = checkPos(trees.length - 2, trees);
                    //a really long way of checking if either operand is a level equal to or greater than the current level
                    if ((top && top.type() == 'internal' && top.val().type() == 'LEVELS' && STARtoAL.symToLvl[top.val().val()] >= STARtoAL.symToLvl[procTokens[i].val()])
                        || (top2nd && top2nd.type() == 'internal' && top2nd.val().type() == 'LEVELS' && STARtoAL.symToLvl[top2nd.val().val()] >= STARtoAL.symToLvl[procTokens[i].val()]))
                        break;
                    trees.push(new ASTNode('internal', opers.pop(), [trees.pop(), trees.pop()].reverse()));
                }
                var temp = [];
                while (trees.length > 0) {
                    var top = checkPos(trees.length - 1, trees);
                    if (top && top.type() == 'internal' && top.val().type() == 'LEVELS' && STARtoAL.symToLvl[top.val().val()] >= STARtoAL.symToLvl[procTokens[i].val()])
                        break;
                    
                    temp.push(trees.pop());
                }
                
                trees.push(new ASTNode('internal', procTokens[i], temp.reverse()));
                break;
            case 'PLUS':
                while (opers.length > 0) {
                    var topOper = checkPos(opers.length - 1, opers);
                    if (topOper && topOper.type() == 'MUL') {
                        trees.push(new ASTNode('internal', opers.pop(), [trees.pop(), trees.pop()].reverse()));
                    } else break;
                }
                
                opers.push(procTokens[i]);
                break;
            case 'MUL':
                opers.push(procTokens[i]);
                break;
            case 'LPAREN':
                var j = i + 1, pc = 1;
                while (j < procTokens.length && pc > 0) {
                    if (procTokens[j].type() == 'RPAREN')
                        pc--;
                    else if (procTokens[j].type() == 'LPAREN')
                        pc++;
                    j++;
                }
                
                var paren_trees = STARtoAL.SYA(procTokens.slice(i + 1, j - 1));
                
                for (var i in paren_trees)
                    trees.push(paren_trees[i]); //assuming no slashes are allowed in parentheses
                
                var prevTok = checkPos(i - 1, procTokens);
                
                i = j - 1;
                break;
            case 'RPAREN':
                console.log('Mismatched parens.');
                break;
            case 'FORSLASH': //if a slash is encountered, it's as if we've started another statement, so clean up the current output queue, save it and start a new one
                while (opers.length > 0 && trees.length > 1)
                    trees.push(new ASTNode('internal', opers.pop(), [trees.pop(), trees.pop()].reverse()));
            
                glob_trees.push(trees);
                trees = []; opers = [];
                break;
            case 'END':
            	//aaand we're done
                break tstream;
        }
    }
    while (opers.length > 0 && trees.length > 1)
        trees.push(new ASTNode('internal', opers.pop(), [trees.pop(), trees.pop()].reverse()));
        
    glob_trees.push(trees);
    
    for (var i in glob_trees) {
        if (glob_trees[i] instanceof Array)
            glob_trees[i] = glob_trees[i][0];
    }
    
    return glob_trees;
};

STARtoAL._flattenAST = function(ast) {
    if (ast.type() == 'internal') {
        var ret = new ASTNode(ast.type(), ast.val());
        for (var i = 0; i < ast.length; i++) {
            if (ast.children(i).type() == 'internal' && ret.val().val() == ast.children(i).val().val()) {
                ret.concat(STARtoAL._flattenAST(ast.children(i)));
            } else {
                ret.push(STARtoAL._flattenAST(ast.children(i)));
            }
        }
        return ret;
    }
    return ast;
};

STARtoAL._descPriority = {
    'LEVELS':0,
    'PLUS':1
};

STARtoAL._expandAST = function(ast) {
    if (ast.type() == 'internal') {
        var ret = null;
        
        //TODO: if type is LEVELS, force level upgrade on children
        if (ast.val().type() == 'MUL') {
            ret = new ASTNode('internal', new Token('PLUS', '+'));
            
            /*for(var i=0; i<ast.length; i++) {
                if (ast.children(i).type() == 'internal') {
                    if (ret == null)
                       ret = new ASTNode('internal', ast.children(i).val());
                    else {
                        if (STARtoAL._descPriority[ret.val().type()] > STARtoAL._descPriority[ast.children(i).val().type()])
                            ret = new ASTNode('internal', ast.children(i).val());
                    }
                } else if (i+1 == ast.length && ret == null) {
                    ret = new ASTNode('internal', new Token('PLUS', '+'));
                }
            }*/
            
            for(var k=0; k<ast.length-1; k++) {
                var a = STARtoAL._expandAST(ast.children(k)), b = STARtoAL._expandAST(ast.children(k+1));
                
                if (a.type() == 'internal') {
                    if (b.type() == 'internal') {
                        for (var i=0; i<a.length; i++) {
                            for (var j=0; j<b.length; j++)
                                ret.push(STARtoAL._expandAST(new ASTNode(ast.type(), ast.val(), [a.children(i), b.children(j)])));
                        }
                    } else {
                        for (var i=0; i<a.length; i++)
                            ret.push(STARtoAL._expandAST(new ASTNode(ast.type(), ast.val(), [a.children(i), b])));
                    }
                } else {
                    if (b.type() == 'internal') {
                        for (var j=0; j<b.length; j++)
                            ret.push(STARtoAL._expandAST(new ASTNode(ast.type(), ast.val(), [a, b.children(j)])));
                    } else {
                        if (Triple.checkMerge(a.val(), b.val())) {
                            return new ASTNode('value', Triple.merge(a.val(), b.val()));
                        } else {
                            return ast;
                        }
                    }
                }
            }
        } else if (ast.val().type() == 'LEVELS') {
            var tast = STARtoAL._expandAST(ast.children(0));
            
            ret = STARtoAL._forceLevel(tast, STARtoAL.symToLvl[ast.val().val()]);
        } else if (ast.val().type() == 'PLUS') {
            ret = new ASTNode(ast.type(), ast.val());
            
            for (var i=0; i<ast.length; i++) {
                var tast = STARtoAL._expandAST(ast.children(i));
                if (tast.type() == 'internal') {
                    ret.concat(tast);
                } else {
                    ret.push(tast);
                }   
            }
        } else {
            ret = new ASTNode(ast.type(), ast.val());
            for (var i=0; i<ast.length; i++)
                ret.push(STARtoAL._expandAST(ast.children(i)));
        }
        return ret;
    }
    
    return ast;
};

STARtoAL._forceLevel = function(ast, level) {
    var ret = new ASTNode(ast.type(), ast.val());
    
    if (ast.type() == 'internal') {
        for (var i=0; i<ast.length; i++) {
            var tast = STARtoAL._forceLevel(ast.children(i), level);
            
            ret.push(tast);
        }
    } else {
        if (ast.val().level() < level) {
            ret.val().level(level);
        }
    }
    
    return ret;
};

STARtoAL._fixLayer0 = function(ast) {
    var ret;
    if (ast.type() == 'internal') {
        ret = new ASTNode(ast.type(), ast.val());
        for (var i = 0; i < ast.length; i++) {
            ret.push(STARtoAL._fixLayer0(ast.children(i)));
        }
    } else if (ast.val().level() == 0) {
        ret = new ASTNode('internal', new Token('LEVELS', ':'), [ast]);
    } else {
        ret = ast;
    }
    
    return ret;
};

STARtoAL._sortAdd = function(ast) {
    if (ast.type() == 'internal') {
        var ret = new ASTNode(ast.type(), ast.val());
        for (var i=0; i<ast.length; i++)
            ret.push(STARtoAL._sortAdd(ast.children(i)));
        
        if (ast.val().type() == 'PLUS') {
            ret.setChildren(ret.children().sort(function(a,b) {
                if (a.type() == 'internal' && b.type() == 'value') return -1;
                else if (a.type() == 'value' && b.type() == 'internal') return 1;
                else if (a.type() == 'internal' && b.type() == 'internal') return 0;
                else {
                    var bstr = b.val().getCompleteStr(), astr = a.val().getCompleteStr();
                    
                    if (astr.length != bstr.length) return astr.length - bstr.length
                    
                    for (var i=0; i<astr.length; i++)
                        if (astr[i] != bstr[i])
                            return STARtoAL.primitiveVal[astr[i]] - STARtoAL.primitiveVal[bstr[i]];
                    
                    return 0;
                }
            }));
        }
        
        return ret;
    }
    
    return ast;
};

STARtoAL._postSYA = function(ast) {
    //console.log('tree: ', STARtoAL.astObjectStr(ast), ast.toString());
    var ret = STARtoAL._fixLayer0(ast);
    //console.log('postfix: ', STARtoAL.astObjectStr(ret), ret.toString());
    ret = STARtoAL._expandAST(ret);
    ret = STARtoAL._flattenAST(ret);
    ret = STARtoAL._sortAdd(ret);
    
    return ret;
};

STARtoAL.convert = function(str) {
    //tokenize initial string
    var tokens = STARtoAL._tokenize(str);
    //console.log('tokens: ' + STARtoAL.tokensToStr(tokens) + ' ,', tokens);
    
    //preprocess tokens
    var procTokens = STARtoAL._preprocess(tokens);
    //console.log('prep. tokens: ' + STARtoAL.tokensToStr(procTokens) + ' ,', procTokens);
    procTokens = STARtoAL._removeImplicitMul(procTokens);
    //console.log('mul. tokens: ' + STARtoAL.tokensToStr(procTokens) + ' ,', procTokens);
    
    //create (multiple) abstract syntax tree(s) from the stream of tokens
    var tree_arr = STARtoAL.SYA(procTokens);
    //console.log('state: ', STARtoAL.astToStr(tree_arr));
    //console.log('state: ', array_map(tree_arr, STARtoAL.astObjectStr));

    for (var i in tree_arr) {
        //expand ASTs wherever there are valid multiplication statements
        //console.log(STARtoAL.astObjectStr(tree_arr[i]));
        tree_arr[i] = STARtoAL._postSYA(tree_arr[i]);
    }
        
    return tree_arr;
};

STARtoAL.multiConvert = function(str) {
    var parts = str.match(/\*[^*]+\*\*/g), asts = [];
    for (var i in parts)
        asts.push(STARtoAL.convert(parts[i]));
    return asts;
};

//var arr = STARtoAL.multiConvert("*(U:A:S:.+A:U:T:.+T:U:A:.)/S:B:B:.U:B:S:.T:A:U:.-E:S:S:.U:B:S:.T:A:U:.- E:B:S:.T:B:S:.A:A:U:.-'** *(A:+S:)(S:)O:.**");
//var arr = STARtoAL.multiConvert("*O:U:.-S:B:.-**");
//var arr = STARtoAL.multiConvert("*O:M:.-A:M:.-O:M:.-'**");
//console.log(arr);
//console.log(array_map(arr, function(i) { return array_map(i, STARtoAL.astToStr); }));
//console.log(array_map(arr, function(i) { return array_map(i, STARtoAL.astObjectStr); }));

//console.log(STARtoAL._forceLevel(a = new ASTNode('internal', new Token('LEVELS', '-'), [new ASTNode('internal', new Token('LEVELS', '.'), [new ASTNode('value', new Triple(0, 'A'))])]), 2).toString());
