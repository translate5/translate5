export default class TagsModeProvider {
    constructor() {
        // TODO change this to get rid of Ext dependency (app state?)
        this.viewModesController = Editor.app.getController('ViewModes');
    }

    isFullTagMode() {
        return this.viewModesController.isFullTag();
    }
}
