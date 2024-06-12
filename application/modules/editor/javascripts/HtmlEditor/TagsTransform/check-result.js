export default class CheckResult {
    #missingTags;
    #duplicatedTags;
    #excessTags;
    #tagsOrderCorrect;

    constructor(missingTags, duplicatedTags, excessTags, tagsOrderCorrect) {
        this.#missingTags = missingTags;
        this.#duplicatedTags = duplicatedTags;
        this.#excessTags = excessTags;
        this.#tagsOrderCorrect = tagsOrderCorrect;
    }

    isSuccessful() {
        return this.#missingTags.length === 0
            && this.#duplicatedTags.length === 0
            && this.#excessTags.length === 0
            && this.#tagsOrderCorrect === true;
    }
}
