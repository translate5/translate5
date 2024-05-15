export default class CheckResult {
    constructor(missingTags, duplicatedTags, excessTags) {
        this.missingTags = missingTags;
        this.duplicatedTags = duplicatedTags;
        this.excessTags = excessTags;
        this.tagsOrderCorrect = true;
    }

    isSuccessful() {
        return this.missingTags.length === 0
            && this.duplicatedTags.length === 0
            && this.excessTags.length === 0
            && this.tagsOrderCorrect === true;
    }
}
