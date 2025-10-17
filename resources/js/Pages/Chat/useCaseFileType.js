import CodeIcon from '@/Components/UI/Icons/CodeIcon.vue';
import DefaultIcon from '@/Components/UI/Icons/DefaultIcon.vue';
import DocIcon from '@/Components/UI/Icons/DocIcon.vue';
import PdfIcon from '@/Components/UI/Icons/PdfIcon.vue';
import PngIcon from '@/Components/UI/Icons/PngIcon.vue';
export function useCaseFileType() {
  function getColorByFileName(filename) {
    const extension = filename.substring(filename.lastIndexOf('.') + 1);
    switch (extension.toLowerCase()) {
      case 'pdf':
        return 'text-red-600';
      case 'png':
      case 'jpg':
      case 'jpeg':
        return 'text-blue-600';
      case 'js':
      case 'ts':
      case 'vue':
        return 'text-yellow-600';
      case 'doc':
      case 'docx':
        return 'text-green-600';
      default:
        return 'text-gray-600';
    }
  }

  function getIconByFileName(filename) {
    const extension = filename.substring(filename.lastIndexOf('.') + 1);
    switch (extension.toLowerCase()) {
      case 'pdf':
        return PdfIcon;
      case 'png':
      case 'jpg':
      case 'jpeg':
        return PngIcon;
      case 'js':
      case 'ts':
      case 'vue':
        return CodeIcon;
      case 'doc':
      case 'docx':
        return DocIcon;
      default:
        return DefaultIcon;
    }
  }

  const formatFileSize = (bytes) => {
    if (!bytes) return '';
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(1024));
    return Math.round((bytes / Math.pow(1024, i)) * 100) / 100 + ' ' + sizes[i];
  };
  return { getColorByFileName, getIconByFileName, formatFileSize };
}
