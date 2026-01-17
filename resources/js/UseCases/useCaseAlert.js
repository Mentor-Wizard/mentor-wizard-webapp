import { ref } from 'vue';

const isRinging = ref(false);

export function useAlerts() {
  function infoChatMessage(user_id) {
    if (user_id) {
      Echo.private(`Chat.${user_id}`).listen('Chats\\ChatMessageEvent', (e) => {
        const chat = e.chat;
        if (!chat.mute) ringBell();
      });
    }
  }

  function ringBell() {
    isRinging.value = false;
    requestAnimationFrame(() => {
      isRinging.value = true;
      setTimeout(() => {
        isRinging.value = false;
      }, 2000);
    });
  }

  return { infoChatMessage, isRinging };
}
