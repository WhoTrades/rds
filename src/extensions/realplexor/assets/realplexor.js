/*
 * Initializatation realplexor variable.
 */
if (window.Dklab_Realplexor)
{
    window.Realplexor = function(fullUrl, namespace, viaDocumentWrite) {
        this.__setcursor_calls = [];
        this.__subscribe_calls = [];
        this.__unsubscribe_calls = [];
        this.__unsubscribeAll_calls = false;
        this.dr = new Dklab_Realplexor(fullUrl, namespace, viaDocumentWrite);
    }

    window.Realplexor.prototype.logon = function() {
        this.dr.logon(Array.prototype.slice.call(arguments, 1));
    }
    window.Realplexor.prototype.setCursor = function(id, cursor) {
        this.__setcursor_calls.push({id:id, cursor:cursor});
        this.dr.setCursor(id, cursor);
        return this;
    }
    window.Realplexor.prototype.subscribe = function(id, callback) {
        this.__subscribe_calls.push({id:id, callback:callback});
        this.dr.subscribe(id, callback);
        return this;
    }
    window.Realplexor.prototype.unsubscribe = function(id, callback) {
        this.__unsubscribe_calls.push({id:id, callback:callback});
        this.dr.unsubscribe(id, callback);
        return this;
    }
    window.Realplexor.prototype.unsubscribeAll = function() {
        this.__unsubscribe_calls = true;
        this.dr._map = {};
        return this;
    }
    window.Realplexor.prototype.execute = function() {
        if (this.__subscribe_calls.length > 0 || this.__unsubscribe_calls.length > 0 || this.__unsubscribeAll_calls) {
            this.dr.execute();
            this.__subscribe_calls = [];
            this.__unsubscribe_calls = [];
            this.__unsubscribeAll_calls = false;
        }
        return this;
    }
}

var realplexors = realplexors || {};

function initRealplexors(server, namespace)
{
    if (!realplexors[server + '-' + namespace]) {
        realplexors[server + '-' + namespace] = new Realplexor(server, namespace);
        realplexors[server + '-' + namespace].__name = server + '-' + namespace;
    }

    var realplexor = realplexors[server + '-' + namespace];
    return realplexor;
}

function resetAllRealplexors()
{
    for (r in realplexors) {
        realplexors[r].unsubscribeAll().execute();
    }
}