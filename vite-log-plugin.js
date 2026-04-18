export default function logFileChanges() {
    return {
        name: 'log-file-changes',
        handleHotUpdate({ file }) {
            console.log(`🔄 HMR triggered by: ${file}`);
        },
    };
}
