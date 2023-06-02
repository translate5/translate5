class CheckResult {
    constructor(missingTags, duplicatedTags, excessTags) {
        this.missingTags = missingTags;
        this.duplicatedTags = duplicatedTags;
        this.excessTags = excessTags;
    }

    isSuccessful() {
        return this.missingTags.length === 0
            && this.duplicatedTags.length === 0
            && this.excessTags.length === 0;
    }
}
