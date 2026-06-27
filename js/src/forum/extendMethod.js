export default function extendMethod(object, method, callback) {
    const original = object[method];

    object[method] = function (...args) {
        const value = original ? original.apply(this, args) : undefined;

        callback.apply(this, [value, ...args]);

        return value;
    };

    if (original) {
        Object.assign(object[method], original);
    }
}
