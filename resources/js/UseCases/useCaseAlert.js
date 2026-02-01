import { ref } from 'vue';

const isRinging = ref(false);
const notificationsCount = ref(0);

export function useAlerts() {
  function infoChatMessage(user_id) {
    if (user_id) {
      Echo.private(`Chat.${user_id}`).listen('Chats\\ChatMessageEvent', (e) => {
        const is_muted = e.is_muted;
        if (!is_muted) ringBell();
      });
      Echo.private(`Chat.${user_id}`).listen(
        'Chats\\UnreadMessagesEvent',
        (e) => {
          notificationsCount.value = e.notificationsCount;
        },
      );
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

  return { infoChatMessage, isRinging, notificationsCount };
}
