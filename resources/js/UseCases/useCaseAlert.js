import { ref } from 'vue';

const isRinging = ref(false);
const notificationsCount = ref(0);

export function useAlerts() {
  function infoChatMessage(user_id) {
    if (user_id) {
      Echo.private(`Chat.${user_id}`).listen('Chats\\ChatMessageEvent', (e) => {
        console.log(e);
        const chat = e.chat;
        if (!chat.mute) ringBell();
      });
      Echo.private(`Chat.${user_id}`).listen(
        'Chats\\UnreadMessagesEvent',
        (e) => {
          console.log(e);
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
