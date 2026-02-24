import EditorHistory from './editor-history.js';

describe('EditorHistory', () => {
    let history;

    beforeEach(() => {
        history = new EditorHistory();
    });

    describe('initialization', () => {
        test('should start with empty history', () => {
            expect(history.size()).toBe(0);
            expect(history.getCurrentIndex()).toBe(-1);
        });

        test('should not allow undo or redo initially', () => {
            expect(history.canUndo()).toBe(false);
            expect(history.canRedo()).toBe(false);
        });

        test('should return null for current snapshot when empty', () => {
            expect(history.getCurrentSnapshot()).toBe(null);
        });
    });

    describe('saveSnapshot', () => {
        test('should save a snapshot', () => {
            history.saveSnapshot('Hello', 5);

            expect(history.size()).toBe(1);
            expect(history.getCurrentIndex()).toBe(0);
        });

        test('should save multiple snapshots', () => {
            history.saveSnapshot('First', 5);
            history.saveSnapshot('Second', 6);
            history.saveSnapshot('Third', 7);

            expect(history.size()).toBe(3);
            expect(history.getCurrentIndex()).toBe(2);
        });

        test('should save snapshot with correct data and cursor position', () => {
            const data = 'Test content';
            const cursorPosition = 10;

            history.saveSnapshot(data, cursorPosition);

            const snapshot = history.getCurrentSnapshot();
            expect(snapshot.data).toBe(data);
            expect(snapshot.cursorPosition).toBe(cursorPosition);
        });

        test('should remove forward history when saving in the middle', () => {
            history.saveSnapshot('First', 1);
            history.saveSnapshot('Second', 2);
            history.saveSnapshot('Third', 3);

            // Move back to first snapshot
            history.moveToPrevious();
            history.moveToPrevious();

            expect(history.getCurrentIndex()).toBe(0);

            // Save new snapshot should remove "Second" and "Third"
            history.saveSnapshot('New', 4);

            expect(history.size()).toBe(2);
            expect(history.getCurrentIndex()).toBe(1);
        });

        test('should limit history size to max (100 by default)', () => {
            // Save 105 snapshots
            for (let i = 0; i < 105; i++) {
                history.saveSnapshot(`Content ${i}`, i);
            }

            expect(history.size()).toBe(100);
            expect(history.getCurrentIndex()).toBe(99);

            // The first 5 snapshots should be removed
            const currentSnapshot = history.getCurrentSnapshot();
            expect(currentSnapshot.data).toBe('Content 104');
        });
    });

    describe('getCurrentSnapshot', () => {
        test('should return null when history is empty', () => {
            expect(history.getCurrentSnapshot()).toBe(null);
        });

        test('should return current snapshot', () => {
            history.saveSnapshot('First', 1);
            history.saveSnapshot('Second', 2);

            const snapshot = history.getCurrentSnapshot();
            expect(snapshot.data).toBe('Second');
            expect(snapshot.cursorPosition).toBe(2);
        });

        test('should return correct snapshot after moving in history', () => {
            history.saveSnapshot('First', 1);
            history.saveSnapshot('Second', 2);
            history.saveSnapshot('Third', 3);

            history.moveToPrevious();

            const snapshot = history.getCurrentSnapshot();
            expect(snapshot.data).toBe('Second');
            expect(snapshot.cursorPosition).toBe(2);
        });
    });

    describe('moveToPrevious (undo)', () => {
        test('should return false when no history to undo', () => {
            expect(history.moveToPrevious()).toBe(false);
        });

        test('should return false when already at the beginning', () => {
            history.saveSnapshot('First', 1);
            expect(history.moveToPrevious()).toBe(false);
        });

        test('should move to previous snapshot', () => {
            history.saveSnapshot('First', 1);
            history.saveSnapshot('Second', 2);

            expect(history.moveToPrevious()).toBe(true);
            expect(history.getCurrentIndex()).toBe(0);

            const snapshot = history.getCurrentSnapshot();
            expect(snapshot.data).toBe('First');
        });

        test('should move multiple steps back', () => {
            history.saveSnapshot('First', 1);
            history.saveSnapshot('Second', 2);
            history.saveSnapshot('Third', 3);

            history.moveToPrevious();
            history.moveToPrevious();

            expect(history.getCurrentIndex()).toBe(0);
            const snapshot = history.getCurrentSnapshot();
            expect(snapshot.data).toBe('First');
        });
    });

    describe('moveToNext (redo)', () => {
        test('should return false when no history to redo', () => {
            expect(history.moveToNext()).toBe(false);
        });

        test('should return false when at the end of history', () => {
            history.saveSnapshot('First', 1);
            expect(history.moveToNext()).toBe(false);
        });

        test('should move to next snapshot after undo', () => {
            history.saveSnapshot('First', 1);
            history.saveSnapshot('Second', 2);

            history.moveToPrevious();
            expect(history.moveToNext()).toBe(true);
            expect(history.getCurrentIndex()).toBe(1);

            const snapshot = history.getCurrentSnapshot();
            expect(snapshot.data).toBe('Second');
        });

        test('should move multiple steps forward', () => {
            history.saveSnapshot('First', 1);
            history.saveSnapshot('Second', 2);
            history.saveSnapshot('Third', 3);

            history.moveToPrevious();
            history.moveToPrevious();

            history.moveToNext();
            history.moveToNext();

            expect(history.getCurrentIndex()).toBe(2);
            const snapshot = history.getCurrentSnapshot();
            expect(snapshot.data).toBe('Third');
        });
    });

    describe('canUndo', () => {
        test('should return false when no history', () => {
            expect(history.canUndo()).toBe(false);
        });

        test('should return false at the beginning of history', () => {
            history.saveSnapshot('First', 1);
            expect(history.canUndo()).toBe(false);
        });

        test('should return true when there is previous history', () => {
            history.saveSnapshot('First', 1);
            history.saveSnapshot('Second', 2);

            expect(history.canUndo()).toBe(true);
        });

        test('should return false after undoing to the beginning', () => {
            history.saveSnapshot('First', 1);
            history.saveSnapshot('Second', 2);

            history.moveToPrevious();
            expect(history.canUndo()).toBe(false);
        });
    });

    describe('canRedo', () => {
        test('should return false when no history', () => {
            expect(history.canRedo()).toBe(false);
        });

        test('should return false at the end of history', () => {
            history.saveSnapshot('First', 1);
            history.saveSnapshot('Second', 2);

            expect(history.canRedo()).toBe(false);
        });

        test('should return true after undoing', () => {
            history.saveSnapshot('First', 1);
            history.saveSnapshot('Second', 2);

            history.moveToPrevious();
            expect(history.canRedo()).toBe(true);
        });

        test('should return false after undoing and saving new snapshot', () => {
            history.saveSnapshot('First', 1);
            history.saveSnapshot('Second', 2);

            history.moveToPrevious();
            history.saveSnapshot('New', 3);

            expect(history.canRedo()).toBe(false);
        });
    });

    describe('clear', () => {
        test('should clear empty history', () => {
            history.clear();

            expect(history.size()).toBe(0);
            expect(history.getCurrentIndex()).toBe(-1);
        });

        test('should clear history with snapshots', () => {
            history.saveSnapshot('First', 1);
            history.saveSnapshot('Second', 2);
            history.saveSnapshot('Third', 3);

            history.clear();

            expect(history.size()).toBe(0);
            expect(history.getCurrentIndex()).toBe(-1);
            expect(history.canUndo()).toBe(false);
            expect(history.canRedo()).toBe(false);
            expect(history.getCurrentSnapshot()).toBe(null);
        });
    });

    describe('size', () => {
        test('should return 0 for empty history', () => {
            expect(history.size()).toBe(0);
        });

        test('should return correct size', () => {
            history.saveSnapshot('First', 1);
            expect(history.size()).toBe(1);

            history.saveSnapshot('Second', 2);
            expect(history.size()).toBe(2);

            history.saveSnapshot('Third', 3);
            expect(history.size()).toBe(3);
        });

        test('should update size when removing forward history', () => {
            history.saveSnapshot('First', 1);
            history.saveSnapshot('Second', 2);
            history.saveSnapshot('Third', 3);

            history.moveToPrevious();
            history.moveToPrevious();
            history.saveSnapshot('New', 4);

            expect(history.size()).toBe(2);
        });
    });

    describe('getCurrentIndex', () => {
        test('should return -1 for empty history', () => {
            expect(history.getCurrentIndex()).toBe(-1);
        });

        test('should return correct index', () => {
            history.saveSnapshot('First', 1);
            expect(history.getCurrentIndex()).toBe(0);

            history.saveSnapshot('Second', 2);
            expect(history.getCurrentIndex()).toBe(1);
        });

        test('should update index when moving in history', () => {
            history.saveSnapshot('First', 1);
            history.saveSnapshot('Second', 2);
            history.saveSnapshot('Third', 3);

            expect(history.getCurrentIndex()).toBe(2);

            history.moveToPrevious();
            expect(history.getCurrentIndex()).toBe(1);

            history.moveToPrevious();
            expect(history.getCurrentIndex()).toBe(0);

            history.moveToNext();
            expect(history.getCurrentIndex()).toBe(1);
        });
    });

    describe('complex scenarios', () => {
        test('should handle undo, redo, and save sequence', () => {
            history.saveSnapshot('First', 1);
            history.saveSnapshot('Second', 2);
            history.saveSnapshot('Third', 3);

            // Undo twice
            history.moveToPrevious();
            history.moveToPrevious();
            expect(history.getCurrentSnapshot().data).toBe('First');

            // Redo once
            history.moveToNext();
            expect(history.getCurrentSnapshot().data).toBe('Second');

            // Save new snapshot (should remove "Third")
            history.saveSnapshot('Fourth', 4);
            expect(history.size()).toBe(3);
            expect(history.canRedo()).toBe(false);
        });

        test('should handle edge case with single snapshot', () => {
            history.saveSnapshot('Only', 1);

            expect(history.canUndo()).toBe(false);
            expect(history.canRedo()).toBe(false);
            expect(history.moveToPrevious()).toBe(false);
            expect(history.moveToNext()).toBe(false);
            expect(history.getCurrentSnapshot().data).toBe('Only');
        });

        test('should preserve cursor positions correctly', () => {
            const snapshots = [
                { data: 'First', cursor: 5 },
                { data: 'Second', cursor: 10 },
                { data: 'Third', cursor: 15 }
            ];

            snapshots.forEach(s => history.saveSnapshot(s.data, s.cursor));

            // Check current
            let current = history.getCurrentSnapshot();
            expect(current.cursorPosition).toBe(15);

            // Undo and check
            history.moveToPrevious();
            current = history.getCurrentSnapshot();
            expect(current.cursorPosition).toBe(10);

            history.moveToPrevious();
            current = history.getCurrentSnapshot();
            expect(current.cursorPosition).toBe(5);
        });
    });
});
