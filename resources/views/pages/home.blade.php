<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    />
    <style>
      @import url("https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap");
      :root {
        font-family: "Montserrat", sans-serif;
      }
      .apipara {
        font-style: italic;
      }
      .apibtn {
        background-color: #6d5dd3;
      }
      .herobtn {
        background-color: #6d5dd3;
      }
      .heropara {
        font-style: italic;
      }
      .team {
        background-color: #343434;
      }
      .teampara {
        font-style: italic;
      }
    </style>

    <title>ByteAlley</title>
  </head>
  <body>
    <div class="bg-black gradient-bg">
      <div
        class="bg-black text-white flex-col-reverse gap-7 justify-between flex md:flex-row items-center container mx-auto w-11/12 p-10"
      >
        <div class="md:w-[50%]">
          <h1 class="text-4xl lg:text-6xl flex">
            ByteAlley
            <span>
              <img
                class="w-12 mt-3 lg:w-28 mb-1"
                src="{{asset('asset/API.png')}}"
                alt=""
              />
            </span>
          </h1>
          <h1 class="text-4xl lg:text-6xl">Architecture</h1>
          <h3 class="text-4xl lg:text-4xl capitalize">{{ config('app.env') }} Branch</h3>
          <p class="text-sm my-7 line leading-5 heropara">
            Lorem ipsum dolor sit amet, consectetur <br />
            adipiscing elit. Nunc vulputate libero et velit <br />
            interdum, ac aliquet odio mattis. Class aptent <br />
            taciti sociosqu ad litora torquent per.
          </p>
          <button
            class="herobtn rounded-lg flex p-1 lg:p-3 gap-12 text-xs items-center"
          >
            Check Out Documentation
            <span>
              <img class="w-3" src="{{asset('asset/herovect.png')}}" alt="" />
            </span>
          </button>
        </div>
        <div class="w-full">
          <img
            class="lg:w-4/6 lg:mx-auto object-contain"
            src="{{asset('asset/logo.png')}}"
            alt=""
          />
        </div>
      </div>
    </div>
    <!-- Cards Section -->
    <div class="bg-black text-white p-10">
      <div class="container mx-auto w-11/12">
        <h1 class="text-center text-2xl md:text-5xl lg:my-10 my-2 font-bold">
          PROJECT FEATURES
        </h1>
        <div class="flex flex-col md:flex-row gap-24 items-center">
          <img class="md:w-[40%]" src="{{asset('asset/virtualimg.png')}}" alt="" />
          <div class="">
            <h2 class="mb-6 text-center md:text-start text-xl md:text-2xl">
              Lorem ipsum dolor sit amet
            </h2>
            <p class="border border-white rounded-lg p-6">
              Torem ipsum dolor sit amet, consectetur adipiscing elit. Nunc
              vulputate libero et velit interdum, ac aliquet odio mattis. Class
              aptent taciti sociosqu ad litora torquent per conubia nostra, per
              inceptos himenaeos. Curabitur tempus urna at turpis condimentum
              lobortis. Ut commodo efficitur neque.
            </p>
          </div>
        </div>
        <div class="flex flex-col md:flex-row gap-24 items-center my-5">
          <div class="">
            <h2 class="mb-6 text-center md:text-start text-xl md:text-2xl">
              Lorem ipsum dolor sit amet
            </h2>
            <p class="border border-white rounded-lg p-6">
              Torem ipsum dolor sit amet, consectetur adipiscing elit. Nunc
              vulputate libero et velit interdum, ac aliquet odio mattis. Class
              aptent taciti sociosqu ad litora torquent per conubia nostra, per
              inceptos himenaeos. Curabitur tempus urna at turpis condimentum
              lobortis. Ut commodo efficitur neque.
            </p>
          </div>
          <img class="md:w-[40%]" src="{{asset('asset/Businessman.png')}}" alt="" />
        </div>
        <div class="flex flex-col md:flex-row gap-24 items-center">
          <img class="md:w-[40%]" src="{{asset('asset/virtualimg.png')}}" alt="" />
          <div class="">
            <h2 class="mb-6 text-center md:text-start text-xl md:text-2xl">
              Lorem ipsum dolor sit amet
            </h2>
            <p class="border border-white rounded-lg p-6">
              Torem ipsum dolor sit amet, consectetur adipiscing elit. Nunc
              vulputate libero et velit interdum, ac aliquet odio mattis. Class
              aptent taciti sociosqu ad litora torquent per conubia nostra, per
              inceptos himenaeos. Curabitur tempus urna at turpis condimentum
              lobortis. Ut commodo efficitur neque.
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- API Detail -->
    <div>
      <div class="bg-black text-white p-10">
        <div class="container mx-auto w-11/12">
          <h2 class="text-center my-10 text-2xl lg:text-4xl font-bold">
            API DETAILS
          </h2>
          <p class="text-xl text-center lg:text-3xl lg:text-left">
            Yorem ipsum dolor sit amet, consectetur adipiscing elit. Nunc
            vulputate libero et velit interdum, ac aliquet odio mattis. Class
            aptent taciti sociosqu ad litora torquent per conubia nostra, per
            inceptos himenaeos. Curabitur tempus
          </p>
        </div>
        <div
          class="flex flex-col md:flex-row my-16 items-center text-start gap-5 container mx-auto w-11/12"
        >
          <img class="md:w-[40%]" src="{{asset('asset/FrameBA.png')}}" alt="" />
          <div class="flex flex-col md:float-start">
            <h3 class="text-xl text-center md:text-left lg:text-3xl">
              Yorem ipsum dolor sit amet, consectetur adipiscing elit. Nunc
              vulputate libero et
            </h3>
            <p class="apipara my-5 text-sm text-center md:text-start">
              Yorem ipsum dolor sit amet, consectetur adipiscing elit. Nunc
              vulputate libero et velit interdum, ac aliquet odio mattis. Class
              aptent taciti sociosqu ad litora torquent per conubia nostra, per
              inceptos himenaeos. <br />
              Curabitur tempus
            </p>
            <div class="w-full flex justify-center md:justify-start md:w-1/2">
              <button
                class="apibtn rounded-lg py-4 flex px-3 md:p-2 gap-12 text-xs text-center items-center"
              >
                &lt;Frontend /&gt;
                <span>
                  <img class="w-3" src="{{asset('asset/herovect.png')}}" alt="" />
                </span>
              </button>
            </div>
          </div>
        </div>
        <div class="bg-black">
          <h1 class="text-center text-4xl my-6">Meet The Team</h1>
        </div>
      </div>
    </div>

    <!-- Team section -->
    <div class="team p-10 text-white">
      <div class="container mx-auto w-11/12">
        <!-- Navigation Buttons -->
        <div class="flex justify-end gap-5 mb-6">
          <button id="prevBtn" class="prev ">
            <i class="bi bi-arrow-left text-4xl text- "></i>
          </button>
          <button id="nextBtn" class="next">
            <i class="bi bi-arrow-right text-4xl text-grey-200"></i>
          </button>
        </div>

        <!-- Card Container -->
        <div
          id="cardContainer"
          class="flex flex-col md:flex-row gap-10 overflow-hidden"
        >
          <!-- Card 1 -->
          <div class="flex flex-col lg:flex-row items-center gap-5 w-full card">
            <img src="{{asset('asset/jordan.png')}}" alt="Jordan Ox Photo" />
            <div>
              <h2 class="text-2xl font-bold">Jordan Ox</h2>
              <p class="my-7 teampara text-sm">
                Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nunc
                vulputate libero et velit interdum, ac aliquet odio mattis.
                Class aptent taciti sociosqu ad litora
              </p>
              <h3>
                <ul class="list-disc pl-5">
                  <li class="font-bold">Team Lead</li>
                </ul>
              </h3>
            </div>
          </div>

          <!-- Card 2 -->
          <div class="flex flex-col lg:flex-row items-center gap-5 w-full card">
            <img src="{{asset('asset/jordan.png')}}" alt="Chinese Lady Photo" />
            <div>
              <h2 class="text-2xl font-bold">Anna Lee</h2>
              <p class="my-7 teampara text-sm">
                Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nunc
                vulputate libero et velit interdum, ac aliquet odio mattis.
                Class aptent taciti sociosqu ad litora
              </p>
              <h3>
                <ul class="list-disc pl-5">
                  <li class="font-bold">Project Manager</li>
                </ul>
              </h3>
            </div>
          </div>

          <!-- Card 3 -->
          <div class="flex flex-col lg:flex-row items-center gap-5 w-full card">
            <img src="{{asset('asset/jordan.png')}}" alt="Jordan Ox Photo" />
            <div>
              <h2 class="text-2xl font-bold">John Doe</h2>
              <p class="my-7 teampara text-sm">
                Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nunc
                vulputate libero et velit interdum, ac aliquet odio mattis.
                Class aptent taciti sociosqu ad litora
              </p>
              <h3>
                <ul class="list-disc pl-5">
                  <li class="font-bold">Software Engineer</li>
                </ul>
              </h3>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="bg-black text-white">
      <div class="container mx-auto w-11/12 p-10">
        <img class="my-3" src="./assets/purpleline.png" alt="" />
        <div class="flex flex-col md:flex-row text-center gap-3 justify-center">
          <h3>All Rights Reserved</h3>
          <p>&#169; Copyright 2023</p>
        </div>
        <h3 class="text-center">Designed by Kiane</h3>
      </div>
    </div>

    <script>
      const prevBtn = document.getElementById("prevBtn");
      const nextBtn = document.getElementById("nextBtn");
      const cardContainer = document.getElementById("cardContainer");
      const cards = document.querySelectorAll(".card");
      let currentIndex = 0;

      // Hide all cards except the first one
      cards.forEach((card, index) => {
        if (index !== 0) {
          card.classList.add("hidden");
        }
      });

      // Show the next card
      nextBtn.addEventListener("click", () => {
        cards[currentIndex].classList.add("hidden");
        currentIndex = (currentIndex + 1) % cards.length;
        cards[currentIndex].classList.remove("hidden");
      });

      // Show the previous card
      prevBtn.addEventListener("click", () => {
        cards[currentIndex].classList.add("hidden");
        currentIndex = (currentIndex - 1 + cards.length) % cards.length;
        cards[currentIndex].classList.remove("hidden");
      });
    </script>
  </body>
</html>
