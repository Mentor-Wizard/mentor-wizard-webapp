import { ref } from 'vue';

const isRinging = ref(false);
const mute = ref(false);

export function useAlerts() {
  function infoChatMessage(user_id) {
    if (user_id) {
      getMute();
      Echo.private(`Chat.${user_id}`).listen('Chats\\ChatMessageEvent', (e) => {
        if (!mute.value) ringBell();
      });
    }
  }

  const getMute = async () => {
    const { data } = await axios.get(route('chat.get-mute'));
    console.log(data.mute);
    mute.value = data.mute;
  };

  function ringBell() {
    isRinging.value = false;
    requestAnimationFrame(() => {
      isRinging.value = true;
      setTimeout(() => {
        isRinging.value = false;
      }, 2000);
    });
  }

  return { infoChatMessage, isRinging, mute };
}
