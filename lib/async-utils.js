/**
 * Run provided functions sequentially, collect results, and once the last function finished its execution,
 * call 'onSuccess' function
 *
 * @param functions list of functions to run
 * @param onSuccess function which will be called once all functions finished their execution
 * @param results internal argument, no need to pass it
 */
function runSequence(functions, onSuccess, results) {
    if (!results) {
        results = [];
    }

    if (functions.length > 0) {
        var firstFunction = functions[0];
        firstFunction(function(result) {
            results.push(result);
            setTimeout( // hack to break recursion chain
                function() {
                    runSequence(functions.slice(1), onSuccess, results)
                },
                0
            );
        });
    } else {
        onSuccess(results);
    }
}

/**
 * Run provided functions asynchronously, collect results, and once all functions are finished their execution,
 * call 'onSuccess' function.
 *
 * @param functions list of functions to run
 * @param onSuccess function which will be called once all functions finished their execution
 */
function runAsync(functions, onSuccess)
{
    var results = [];

    functions.forEach(function(currentFunction) {
        currentFunction(function(result) {
            results.push(result);
            if (results.length === functions.length) {
                onSuccess(results);
            }
        });
    });
}