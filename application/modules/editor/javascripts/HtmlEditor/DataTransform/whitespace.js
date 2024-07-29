import Node from "./node";

export default class Whitespace extends Node {
    get length() {
        return 1;
    }
}
