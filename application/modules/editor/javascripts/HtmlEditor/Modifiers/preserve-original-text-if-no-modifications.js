export default function preserveOriginalTextIfNoModifications(text, actions, position) {
    const insertion = actions.find(action => action.type === 'insert');

    if (text === '' && insertion !== undefined) {
        return [insertion.content, Infinity];
    }

    return [text, position];
}
