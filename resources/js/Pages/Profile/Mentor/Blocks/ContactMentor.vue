<template>
  <div>
    <h2 class="text-lg font-semibold text-gray-900">Contact Michael</h2>
    <p class="mt-1 text-sm text-gray-600">
      Have questions before booking a session? <br />
      Send Michael a message.
    </p>

    <form class="mt-4 space-y-4">
      <div>
        <label for="subject" class="block text-sm font-medium text-gray-700"
          >Subject</label
        >
        <input
          type="text"
          id="subject"
          name="subject"
          placeholder="What would you like to discuss?"
          class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
        />
      </div>

      <div>
        <label for="message" class="block text-sm font-medium text-gray-700"
          >Message</label
        >
        <textarea
          id="message"
          name="message"
          rows="4"
          placeholder="Your message..."
          mentor_profiles
          class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
        ></textarea>
      </div>

      <button
        type="submit"
        class="w-full rounded-md bg-gray-100 px-4 py-2 text-sm font-medium text-gray-800 hover:bg-gray-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
      >
        Send Message
      </button>
    </form>
  </div>
</template>
