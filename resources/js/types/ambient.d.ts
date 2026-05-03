declare module '@fontsource-variable/*' {
    const content: any;
    export default content;
}

declare module '@fontsource/*' {
    const content: any;
    export default content;
}

declare module '*.css' {
    const content: any;
    export default content;
}

declare module '*.vue' {
    import type { DefineComponent } from 'vue';
    const component: DefineComponent<object, object, any>;
    export default component;
}
